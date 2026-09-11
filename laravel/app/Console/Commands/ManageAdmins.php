<?php

namespace App\Console\Commands;

use App\Contracts\Office\AdminPasswords;
use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * L'équipe du back-office, en ligne de commande.
 *
 * **C'est la seule façon de créer le premier administrateur**, et c'est
 * délibéré : aucun écran d'inscription n'existe pour le back-office. Un
 * formulaire « devenir administrateur », même caché, est une porte qu'on
 * finit par trouver. Ensuite, les administrateurs s'ajoutent entre eux depuis
 * l'écran « Équipe », et chaque ajout laisse une ligne au journal.
 *
 *   php artisan vayla:admin prenom@vayla.mg --nom="Hanta Rabe"   # ajouter
 *   php artisan vayla:admin prenom@vayla.mg --reinitialiser       # mot de passe perdu
 *   php artisan vayla:admin prenom@vayla.mg --retirer             # retirer
 *   php artisan vayla:admin --liste                                # qui entre
 *
 * **Le mot de passe affiché est provisoire**, et ne s'affiche qu'une fois : il
 * est passé par ce terminal, donc par un autre regard que celui de la personne.
 * À la première connexion, le back-office n'ouvre que l'écran qui demande d'en
 * choisir un.
 */
class ManageAdmins extends Command
{
    protected $signature = 'vayla:admin
                            {email? : L’adresse du membre}
                            {--nom= : Son nom, tel qu’il apparaîtra au journal}
                            {--retirer : Retire ce membre de l’équipe}
                            {--reinitialiser : Donne un nouveau mot de passe provisoire}
                            {--liste : Affiche l’équipe}';

    protected $description = 'Ajoute, retire ou liste les membres de l’équipe du back-office';

    public function handle(): int
    {
        if ($this->option('liste') || ! $this->argument('email')) {
            return $this->lister();
        }

        $email = mb_strtolower(trim((string) $this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => 'email:rfc'])->fails()) {
            $this->error("« {$email} » n’est pas une adresse e-mail.");

            return self::FAILURE;
        }

        return match (true) {
            (bool) $this->option('retirer') => $this->retirer($email),
            (bool) $this->option('reinitialiser') => $this->reinitialiser($email),
            default => $this->ajouter($email),
        };
    }

    private function reinitialiser(string $email): int
    {
        $admin = Admin::query()->where('email', $email)->first();

        if (! $admin) {
            $this->error("{$email} ne fait pas partie de l’équipe.");

            return self::FAILURE;
        }

        $this->montrer($admin, app(AdminPasswords::class)->provisoire($admin));

        return self::SUCCESS;
    }

    /** Le mot de passe provisoire, une fois, et ce qu'il faut en faire. */
    private function montrer(Admin $admin, string $motDePasse): void
    {
        $this->newLine();
        $this->line("  Adresse       : <options=bold>{$admin->email}</>");
        $this->line("  Mot de passe  : <options=bold>{$motDePasse}</>  (provisoire)");
        $this->line("  Back-office   : {$this->adresse()}/connexion");
        $this->newLine();
        $this->comment('Transmettez-le de vive voix. Il ne sera plus affiché : à la première connexion,');
        $this->comment('le back-office demandera d’en choisir un.');
    }

    private function ajouter(string $email): int
    {
        if (Admin::query()->where('email', $email)->exists()) {
            $this->warn("{$email} fait déjà partie de l’équipe.");

            return self::SUCCESS;
        }

        $nom = trim((string) ($this->option('nom') ?: $this->ask('Son nom (il apparaîtra au journal)')));

        if ($nom === '') {
            $this->error('Un nom est nécessaire : le journal doit dire qui a fait quoi.');

            return self::FAILURE;
        }

        $admin = Admin::create(['name' => $nom, 'email' => $email]);

        $this->info("{$nom} fait partie de l’équipe.");
        $this->montrer($admin, app(AdminPasswords::class)->provisoire($admin));

        return self::SUCCESS;
    }

    /** L'adresse du back-office, avec le schéma et le port du site : `office.localhost:8070`. */
    private function adresse(): string
    {
        $site = parse_url((string) config('app.url'));
        $port = isset($site['port']) ? ':'.$site['port'] : '';

        return ($site['scheme'] ?? 'http').'://'.config('vayla.office.domaine').$port;
    }

    private function retirer(string $email): int
    {
        $admin = Admin::query()->where('email', $email)->first();

        if (! $admin) {
            $this->error("{$email} ne fait pas partie de l’équipe.");

            return self::FAILURE;
        }

        $admin->delete();
        $this->info("{$admin->name} n’a plus accès au back-office. Ses gestes restent au journal, sous son nom.");

        return self::SUCCESS;
    }

    private function lister(): int
    {
        $equipe = Admin::query()->orderBy('name')->get();

        if ($equipe->isEmpty()) {
            $this->warn('Personne n’a encore accès au back-office. Pour ajouter le premier membre :');
            $this->line('  php artisan vayla:admin prenom@vayla.mg --nom="Prénom Nom"');

            return self::SUCCESS;
        }

        $this->table(['Nom', 'Adresse', 'Mot de passe', 'Dernière connexion'], $equipe->map(fn (Admin $a) => [
            $a->name, $a->email, $a->motDePasseChoisi() ? 'choisi' : 'provisoire', $a->last_login_at?->diffForHumans() ?? 'jamais',
        ])->all());

        return self::SUCCESS;
    }
}
