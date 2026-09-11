<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Owner;
use App\Models\User;
use App\Services\Office\OfficeAuthService;
use App\Services\OwnerKeyService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Les comptes de test des trois portes — propriétaire, voyageur, back-office —
 * et la fiche qui dit comment entrer par chacune.
 *
 * **Uniquement en local.** Un compte au mot de passe imprimé dans un terminal
 * n'a rien à faire en production ; la commande refuse ailleurs.
 *
 * **Pourquoi une commande et pas un seeder.** Les tests appellent `seed()` et
 * comptent les comptes voyageurs (`RegistrationTest`, `SocialAuthTest`) : des
 * voyageurs semés partout y fausseraient chaque compte. Et un mot de passe en
 * dur dans un seeder versionné finirait par tourner là où il ne doit pas.
 *
 * Tout est **en `@demo.vayla.test`** : le domaine `.test` n'est routable nulle
 * part, et en développement les codes partent dans le bac à sable SMTP — on
 * les lit là, aucune vraie boîte ne reçoit rien.
 *
 * Idempotente : on la relance après un `make seed` (qui recrée les
 * réservations de démonstration et détache donc les voyageurs de test). **Le
 * mot de passe du compte d'équipe de test change à chaque passage** : il n'est
 * lisible qu'à sa création.
 */
class SeedTestAccounts extends Command
{
    protected $signature = 'vayla:comptes-test';

    protected $description = 'Crée ou rafraîchit les comptes de test (local uniquement) et affiche comment entrer';

    /** Les voyageurs de test, rattachés aux réservations de démonstration par l'adresse. */
    private const VOYAGEURS = [
        ['email' => 'claire@demo.vayla.test', 'first_name' => 'Claire', 'last_name' => 'Fontaine', 'phone' => '+33 6 12 45 78 90', 'references' => ['VY-7K2M4']],
        ['email' => 'miora@demo.vayla.test', 'first_name' => 'Miora', 'last_name' => 'Andrianina', 'phone' => '+261 34 55 21 08', 'references' => ['VY-3QX8Z']],
        ['email' => 'anne-sophie@demo.vayla.test', 'first_name' => 'Anne-Sophie', 'last_name' => 'Berger', 'phone' => '+32 470 88 12 33', 'references' => ['VY-5MB7W']],
    ];

    public function handle(OwnerKeyService $cles): int
    {
        if (! app()->environment('local')) {
            $this->error('Réservé au développement local : aucun compte de test en dehors.');

            return self::FAILURE;
        }

        $site = rtrim((string) config('app.url'), '/');
        $office = $this->office();

        $this->line('# Accès de test — Vayla (développement)');
        $this->newLine();
        $this->line('_Généré par `php artisan vayla:comptes-test` le '.Carbon::now()->translatedFormat('j F Y à H\hi').'. Local uniquement._');
        $this->newLine();
        $this->line('Les codes à six chiffres partent dans le bac à sable Mailtrap : aucune vraie boîte ne reçoit rien, on les lit dans l’inbox Mailtrap.');

        // ── Back-office ─────────────────────────────────────────────────
        $motDePasse = Str::password(20, symbols: false);
        $equipe = Admin::query()->updateOrCreate(['email' => 'equipe@demo.vayla.test'], ['name' => 'Équipe Test']);
        // Un mot de passe **choisi**, pas provisoire : c'est un compte de test,
        // il doit ouvrir le back-office en entier dès la première connexion.
        app(OfficeAuthService::class)->choisir($equipe, $motDePasse);

        $this->newLine();
        $this->line('## Back-office');
        $this->newLine();
        $this->line("Adresse : {$office}/connexion");
        $this->newLine();
        $this->line('| Compte | Identifiant | Mot de passe |');
        $this->line('|---|---|---|');
        $this->line("| Équipe Test | `equipe@demo.vayla.test` | `{$motDePasse}` |");

        // ── Propriétaires ───────────────────────────────────────────────
        $this->newLine();
        $this->line('## Propriétaires (avec logements)');
        $this->newLine();
        $this->line("Connexion : {$site}/proprietaire/connexion — l’adresse, puis le code lu dans Mailtrap. Ou directement par le lien d’accès (celui que Vayla envoie sur WhatsApp).");
        $this->newLine();
        $this->line('| Propriétaire | Identifiant | Logements | Demandes en attente | Lien d’accès direct |');
        $this->line('|---|---|---|---|---|');

        Owner::query()
            ->where('is_demo', true)
            ->withCount(['listings', 'bookings as attente_count' => fn ($q) => $q->where('bookings.status', BookingStatus::Pending->value)])
            ->has('listings')
            ->orderByDesc('attente_count')
            ->get()
            ->each(function (Owner $o) use ($cles) {
                if (! $o->access_key) {
                    $cles->tourner($o);
                }

                $this->line("| {$o->name} | `{$o->email}` | {$o->listings_count} | {$o->attente_count} | {$cles->lien($o->refresh())} |");
            });

        // ── Voyageurs ───────────────────────────────────────────────────
        $this->newLine();
        $this->line('## Clients (voyageurs)');
        $this->newLine();
        $this->line("Connexion : {$site}/connexion/client — l’adresse, puis le code lu dans Mailtrap. Il n’y a pas de mot de passe côté voyageur.");
        $this->newLine();
        $this->line('| Voyageur | Identifiant | Réservations rattachées |');
        $this->line('|---|---|---|');

        foreach (self::VOYAGEURS as $v) {
            User::query()->firstOrNew(['email' => $v['email']])->forceFill([
                'first_name' => $v['first_name'],
                'last_name' => $v['last_name'],
                'phone' => $v['phone'],
                'email_verified_at' => Carbon::now(),
            ])->save();

            // Les séjours se rattachent par l'adresse, comme en production.
            $rattachees = Booking::query()->whereIn('reference', $v['references'])->get();
            $rattachees->each(fn (Booking $b) => $b->update(['traveller_email' => $v['email']]));

            $liste = $rattachees->map(fn (Booking $b) => "{$b->reference} ({$b->status->label()})")->implode(', ') ?: 'aucune — relancer après `make seed`';

            $this->line("| {$v['first_name']} {$v['last_name']} | `{$v['email']}` | {$liste} |");
        }

        $this->newLine();
        $this->line('Après un `make seed` ou un `make fresh`, relancer `php artisan vayla:comptes-test` : les réservations de démonstration sont recréées sans adresse, et le mot de passe du compte d’équipe de test change.');

        return self::SUCCESS;
    }

    private function office(): string
    {
        $site = parse_url((string) config('app.url'));
        $port = isset($site['port']) ? ':'.$site['port'] : '';

        return ($site['scheme'] ?? 'http').'://'.config('vayla.office.domaine').$port;
    }
}
