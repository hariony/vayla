<?php

namespace Database\Seeders;

use App\Enums\TrustLevel;
use App\Models\Listing;
use App\Models\StayConfirmation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Des séjours confirmés de démonstration.
 *
 * Ils sont FICTIFS, comme les annonces qui les portent, et le bloc le dit à
 * l'écran — pas seulement en haut de page. Une confirmation rapporte les
 * mots d'une personne : elle ne peut pas se contenter du bandeau « Aperçu »
 * commun, elle doit porter son propre avertissement. `is_demo` les fait
 * disparaître avec le reste dès que `vayla.demo` passe à false.
 *
 * Seules les annonces de **niveau 4** en portent, et ce n'est pas un détail
 * de mise en scène : le niveau 4 se définit par « des voyageurs y ont dormi
 * et ont confirmé ». Une annonce de niveau 2 avec des confirmations serait
 * une contradiction dans les données.
 *
 * **Une des confirmations signale un problème**, et c'est délibéré : un jeu
 * de démonstration où tout le monde confirme tout ne prouverait pas que le
 * bloc sait afficher un « non ». C'est pourtant ce qui le distingue d'une
 * moyenne étoilée.
 *
 * Les dates sont relatives à aujourd'hui : semées en dur, elles vieillissent.
 */
class StayConfirmationSeeder extends Seeder
{
    public function run(): void
    {
        $listings = Listing::query()
            ->where('trust_level', TrustLevel::Proven->value)
            ->pluck('id', 'slug');

        foreach ($this->confirmations() as $slug => $rows) {
            if (! isset($listings[$slug])) {
                continue;
            }

            // Même piège que pour les indisponibilités : `stayed_on` se calcule
            // depuis aujourd'hui, donc la clé de `updateOrCreate` glissait d'un
            // jour à chaque nouvelle journée et le seeder republiait les mêmes
            // voyageurs. Un bloc d'avis en double se voit à l'écran.
            StayConfirmation::query()
                ->where('listing_id', $listings[$slug])
                ->where('is_demo', true)
                ->delete();

            foreach ($rows as $row) {
                $stayed = Carbon::today()->subDays($row['il_y_a']);

                StayConfirmation::create([
                    'listing_id' => $listings[$slug],
                    'traveller' => $row['prenom'],
                    'stayed_on' => $stayed->toDateString(),
                    'traveller_from' => $row['de'],
                    'nights' => $row['nuits'],
                    'points' => $row['confirme'],
                    'flagged' => $row['signale'] ?? [],
                    'comment' => $row['mot'] ?? null,
                    'mismatch' => $row['probleme'] ?? null,
                    'is_demo' => true,
                    'confirmed_at' => $stayed->copy()->addDays($row['nuits'] + 1),
                ]);
            }
        }
    }

    private const TOUT = ['photos', 'address', 'amenities', 'price', 'owner', 'cleanliness'];

    /** @return array<string, array<int, array<string, mixed>>> */
    private function confirmations(): array
    {
        return [
            'villa-ambatoloaka' => [
                [
                    'prenom' => 'Hery', 'de' => 'Antananarivo', 'nuits' => 5, 'il_y_a' => 34,
                    'confirme' => self::TOUT,
                    'mot' => 'La villa est exactement celle des photos, y compris la varangue. Le groupe électrogène a pris le relais deux fois sans qu\'on s\'en aperçoive.',
                ],
                [
                    'prenom' => 'Claire', 'de' => 'Lyon', 'nuits' => 7, 'il_y_a' => 71,
                    'confirme' => ['photos', 'address', 'price', 'owner', 'cleanliness'],
                    'signale' => ['amenities'],
                    'mot' => 'Rien à redire sur la maison ni sur l\'accueil. Le lagon est à dix minutes à pied comme annoncé.',
                    'probleme' => 'La climatisation de la troisième chambre ne fonctionnait pas pendant notre séjour.',
                ],
                [
                    'prenom' => 'Tojo', 'de' => 'Majunga', 'nuits' => 4, 'il_y_a' => 118,
                    'confirme' => self::TOUT,
                    'mot' => 'Prix respecté au centime, propriétaire joignable tout le séjour.',
                ],
            ],
            'maison-itasy' => [
                [
                    'prenom' => 'Miora', 'de' => 'Antananarivo', 'nuits' => 3, 'il_y_a' => 26,
                    'confirme' => self::TOUT,
                    'mot' => 'Le ponton est bien privé et la pirogue était là. Cheminée indispensable en juillet, elle a servi tous les soirs.',
                ],
                [
                    'prenom' => 'Rado', 'de' => 'Antsirabe', 'nuits' => 4, 'il_y_a' => 89,
                    'confirme' => ['photos', 'address', 'amenities', 'price', 'owner'],
                    'signale' => ['cleanliness'],
                    'probleme' => 'La maison n\'avait pas été faite avant notre arrivée ; le propriétaire a envoyé quelqu\'un dans l\'heure.',
                ],
            ],
            'lodge-andasibe' => [
                [
                    'prenom' => 'Fanja', 'de' => 'Tamatave', 'nuits' => 2, 'il_y_a' => 41,
                    'confirme' => self::TOUT,
                    'mot' => 'Les indris nous ont réveillés à six heures. Le guide du village connaît la forêt par cœur.',
                ],
                [
                    'prenom' => 'Marc', 'de' => 'Nantes', 'nuits' => 3, 'il_y_a' => 96,
                    'confirme' => self::TOUT,
                    'mot' => 'Lodge conforme en tout point, et la terrasse sur pilotis vaut le déplacement à elle seule.',
                ],
            ],
        ];
    }
}
