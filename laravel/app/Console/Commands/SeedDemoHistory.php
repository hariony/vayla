<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\ConfirmationPoint;
use App\Enums\ListingStatus;
use App\Enums\TrustLevel;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\StayConfirmation;
use App\Services\Settings\SettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Un historique de démonstration, pour voir vivre les statistiques.
 *
 * Une base de développement fraîche tient tout entière dans le mois en cours :
 * les courbes y seraient un point. Cette commande écrit des mois de demandes
 * **passées**, toutes marquées `is_demo`, que l'écran « Statistiques » dit
 * incluses et retire d'un bouton.
 *
 * **Uniquement en local**, et **uniquement dans le passé** : aucune nuit à
 * venir n'est bloquée, aucun calendrier de propriétaire n'est touché. Les
 * règles du produit tiennent aussi pour la démonstration :
 *
 * - **un séjour n'est « effectué » qu'avec une confirmation de voyageur**, et
 *   seulement sur une annonce de niveau 4 — ailleurs, il reste « accepté » :
 *   un séjour non confirmé ne se facture pas ;
 * - le prix et la commission sont **figés** à la demande, comme en vrai.
 *
 * Les lignes se reconnaissent à l'adresse du voyageur
 * (`@historique.demo.vayla.test`) : la commande les reprend à zéro à chaque
 * passage, et `--retirer` les efface. Un `make seed` les efface aussi — il
 * recrée les réservations de démonstration.
 */
class SeedDemoHistory extends Command
{
    protected $signature = 'vayla:historique-demo
                            {--mois=12 : Nombre de mois passés à remplir}
                            {--retirer : Efface l’historique de démonstration}';

    protected $description = 'Écrit des mois de réservations de démonstration passées (local uniquement), pour les statistiques';

    private const DOMAINE = 'historique.demo.vayla.test';

    private const VOYAGEURS = [
        'Claire Fontaine', 'Miora Andrianina', 'Tovo Rakotomalala', 'Anne-Sophie Berger', 'Fanja Rasoanaivo',
        'Julien Moreau', 'Hery Randrianarisoa', 'Sofia Lindqvist', 'Mamy Rabe', 'Lucas Martin',
        'Voahirana Rakoto', 'Emma Dubois', 'Nomena Razafindrabe', 'Pierre Lefèvre', 'Lalao Andriamanana',
    ];

    public function handle(SettingsService $reglages): int
    {
        if (! app()->environment('local')) {
            $this->error('Réservé au développement local.');

            return self::FAILURE;
        }

        $retirees = $this->retirer();

        if ($this->option('retirer')) {
            $this->info("{$retirees} réservation(s) de l’historique de démonstration effacée(s).");

            return self::SUCCESS;
        }

        $annonces = Listing::query()->where('is_demo', true)->where('status', ListingStatus::Published->value)->get();

        if ($annonces->isEmpty()) {
            $this->error('Aucune annonce de démonstration en ligne : lancez d’abord `make seed`.');

            return self::FAILURE;
        }

        $mois = max(1, min(36, (int) $this->option('mois')));
        $taux = $reglages->commission();
        mt_srand(20260911);

        $n = 0;
        $effectues = 0;

        DB::transaction(function () use ($annonces, $mois, $taux, &$n, &$effectues) {
            for ($recul = $mois; $recul >= 1; $recul--) {
                $debutMois = Carbon::today()->startOfMonth()->subMonthsNoOverflow($recul);
                // Une plateforme qui démarre : peu de demandes au début, plus
                // ensuite, avec du bruit — une droite parfaite ferait faux.
                $demandes = (int) round(2 + ($mois - $recul) * 1.4 + mt_rand(0, 4));

                for ($k = 0; $k < $demandes; $k++) {
                    $listing = $annonces[mt_rand(0, $annonces->count() - 1)];
                    $faite = $debutMois->copy()->addDays(mt_rand(0, $debutMois->daysInMonth - 1))->setTime(mt_rand(7, 22), mt_rand(0, 59));
                    $nuits = max((int) $listing->min_nights, mt_rand(2, 7));
                    $arrivee = $faite->copy()->startOfDay()->addDays(mt_rand(4, 35));
                    $depart = $arrivee->copy()->addDays($nuits);

                    // Uniquement dans le passé : aucune nuit à venir n'est prise.
                    if ($depart->gte(Carbon::today())) {
                        continue;
                    }

                    $statut = $this->issue($listing);
                    $reponse = in_array($statut, [BookingStatus::Accepted, BookingStatus::Completed, BookingStatus::Declined], true)
                        ? $faite->copy()->addMinutes(mt_rand(30, 46 * 60))
                        : null;

                    $booking = new Booking([
                        'reference' => $this->reference(),
                        'listing_id' => $listing->id,
                        'traveller' => self::VOYAGEURS[mt_rand(0, count(self::VOYAGEURS) - 1)],
                        'traveller_phone' => '+26134'.str_pad((string) mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT),
                        'traveller_email' => 'voyageur'.mt_rand(1, 400).'@'.self::DOMAINE,
                        'guests' => mt_rand(1, max(1, (int) $listing->guests)),
                        'arrival' => $arrivee->toDateString(),
                        'departure' => $depart->toDateString(),
                        'nights' => $nuits,
                        'price_per_night' => $listing->price,
                        'total' => $listing->price * $nuits,
                        'commission_rate' => $taux,
                        'status' => $statut,
                        'answered_at' => $reponse,
                        'completed_at' => $statut === BookingStatus::Completed ? $depart->copy()->addDay() : null,
                        'closed_reason' => match ($statut) {
                            BookingStatus::Expired => 'Sans réponse du propriétaire',
                            BookingStatus::Declined => 'Dates déjà prises en direct.',
                            BookingStatus::Cancelled => 'Le voyageur a changé ses plans.',
                            default => null,
                        },
                        'is_demo' => true,
                    ]);
                    $booking->created_at = $faite;
                    $booking->updated_at = $reponse ?? $faite;
                    $booking->save();

                    // Un séjour effectué l'est **par une confirmation** — c'est
                    // la règle de la facture, démonstration comprise.
                    if ($statut === BookingStatus::Completed) {
                        StayConfirmation::create([
                            'listing_id' => $listing->id,
                            'booking_id' => $booking->id,
                            'traveller' => strtok($booking->traveller, ' '),
                            'nights' => $nuits,
                            'stayed_on' => $arrivee->toDateString(),
                            'points' => collect(ConfirmationPoint::cases())->random(mt_rand(3, 6))->map->value->values()->all(),
                            'flagged' => mt_rand(0, 5) === 0 ? [ConfirmationPoint::Cleanliness->value] : [],
                            'is_demo' => true,
                            'confirmed_at' => $depart->copy()->addDays(mt_rand(1, 4)),
                        ]);
                        $effectues++;
                    }

                    $n++;
                }
            }
        });

        $this->info("{$n} demandes de démonstration écrites sur {$mois} mois, dont {$effectues} séjours confirmés.");
        $this->line('Toutes marquées « démonstration » : l’écran Statistiques le dit, et les retire d’un bouton.');
        $this->line('Pour les effacer : php artisan vayla:historique-demo --retirer');

        return self::SUCCESS;
    }

    /**
     * L'issue d'une demande passée. Un séjour n'est « effectué » que sur une
     * annonce de niveau 4, où un voyageur a pu le confirmer ; ailleurs, il
     * reste accepté — et ne se facture pas.
     */
    private function issue(Listing $listing): BookingStatus
    {
        $tirage = mt_rand(1, 100);

        return match (true) {
            $tirage <= 58 => $listing->trust_level === TrustLevel::Proven ? BookingStatus::Completed : BookingStatus::Accepted,
            $tirage <= 74 => BookingStatus::Declined,
            $tirage <= 90 => BookingStatus::Expired,
            default => BookingStatus::Cancelled,
        };
    }

    /** `VY-` et cinq caractères sans O/0 ni I/1 — la forme exacte de la production. */
    private function reference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $ref = 'VY-'.collect(range(1, 5))->map(fn () => $alphabet[mt_rand(0, strlen($alphabet) - 1)])->implode('');
        } while (Booking::query()->where('reference', $ref)->exists());

        return $ref;
    }

    private function retirer(): int
    {
        $ids = Booking::query()->where('traveller_email', 'like', '%@'.self::DOMAINE)->pluck('id');

        StayConfirmation::query()->whereIn('booking_id', $ids)->delete();

        return Booking::query()->whereIn('id', $ids)->delete();
    }
}
