<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\MessageAuthor;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\Listing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Des réservations de démonstration, pour que l'espace propriétaire ait
 * quelque chose à montrer.
 *
 * Sans elles, le bloc « À répondre » — **le seul de l'écran qui porte des
 * boutons**, et la raison pour laquelle un propriétaire ouvre Vayla — est
 * toujours vide en démonstration, et le calendrier n'a jamais de nuits
 * vendues à distinguer de ses périodes déclarées. On ne peut ni montrer
 * l'écran, ni vérifier sa mise en page.
 *
 * Trois choix pour que la démonstration prouve quelque chose :
 *
 * — **Une demande à moins de douze heures.** Le compte à rebours change de
 *   nature sous ce seuil et passe en terre : un jeu où tout expire dans
 *   quarante heures ne montrerait jamais l'urgence.
 * — **Une demande sur un logement de Hanta et une sur un logement d'un
 *   autre propriétaire.** C'est ce qui rend visible que chaque espace ne
 *   voit que le sien.
 * — **Des séjours acceptés à venir**, pour que le calendrier du propriétaire
 *   ait des nuits qu'il ne peut *pas* rouvrir — la distinction que l'écran
 *   existe pour porter.
 *
 * Les références ont **exactement la forme produite en production** — `VY-`
 * plus cinq caractères sans O/0 ni I/1. Un jeu de démonstration qui s'en
 * écarte fait passer les écrans qui valident ce format pour cassés : le
 * formulaire « retrouver ma réservation » refusait les références de démo,
 * et c'est lui qui a révélé l'écart. `BookingSeederTest` tient la règle.
 *
 * Les dates sont **relatives à aujourd'hui**, sinon la démonstration est
 * entièrement dans le passé six mois plus tard. Comme pour les périodes et
 * les confirmations, le seeder **reprend ses lignes à zéro** plutôt que de
 * chercher à les reconnaître : une clé dérivée d'une date glisse d'un jour
 * chaque jour, et `updateOrCreate` créerait un doublon à chaque exécution.
 */
class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $listings = Listing::query()->get()->keyBy('slug');

        Booking::query()->where('is_demo', true)->delete();

        foreach ($this->bookings() as $b) {
            $listing = $listings->get($b['slug']);

            if (! $listing) {
                continue;
            }

            $arrivee = Carbon::today()->addDays($b['dans']);
            $depart = $arrivee->copy()->addDays($b['nuits']);
            $statut = $b['statut'];

            $booking = Booking::create([
                'reference' => $b['reference'],
                'listing_id' => $listing->id,
                'traveller' => $b['voyageur'],
                'traveller_phone' => $b['tel'],
                'guests' => $b['personnes'],
                'message' => $b['message'] ?? null,
                'arrival' => $arrivee->toDateString(),
                'departure' => $depart->toDateString(),
                'nights' => $b['nuits'],
                // Le tarif est **figé à la réservation** : on recopie celui de
                // l'annonce au lieu de le lire à l'affichage. Une facture qui
                // change après coup est une facture qu'on ne paie pas.
                'price_per_night' => $listing->price,
                'total' => $listing->price * $b['nuits'],
                'commission_rate' => config('vayla.commission.rate'),
                'status' => $statut,
                // Seule une demande en attente porte un délai : une demande
                // acceptée qui garderait son compte à rebours finirait par
                // rendre des nuits déjà vendues.
                'hold_expires_at' => $statut === BookingStatus::Pending
                    ? Carbon::now()->addHours($b['heures'])
                    : null,
                'answered_at' => $statut === BookingStatus::Accepted ? Carbon::now()->subDays(2) : null,
                'is_demo' => true,
            ]);

            // Le mot déposé avec la demande ouvre le fil, comme en production.
            if ($b['message'] ?? null) {
                BookingMessage::create([
                    'booking_id' => $booking->id,
                    'author' => MessageAuthor::Traveller,
                    'body' => $b['message'],
                ]);
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function bookings(): array
    {
        return [
            [
                'reference' => 'VY-7K2M4',
                'slug' => 'villa-ambatoloaka',
                'voyageur' => 'Claire Fontaine',
                'tel' => '+33 6 12 45 78 90',
                'personnes' => 4,
                'dans' => 132,
                'nuits' => 6,
                'statut' => BookingStatus::Pending,
                'heures' => 41,
                'message' => 'Bonjour, nous arrivons de Paris avec deux enfants. '
                    .'Est-ce que le transfert depuis l’aéroport est possible ?',
            ],
            // Sous douze heures : c'est le cas qui fait basculer le compte à
            // rebours en terre, et il faut pouvoir le regarder.
            [
                'reference' => 'VY-3QX8Z',
                'slug' => 'bungalow-madirokely',
                'voyageur' => 'Miora Andrianina',
                'tel' => '+261 34 55 21 08',
                'personnes' => 2,
                'dans' => 152,
                'nuits' => 3,
                'statut' => BookingStatus::Pending,
                'heures' => 7,
            ],
            [
                'reference' => 'VY-9DM5R',
                'slug' => 'front-de-mer-amborovy',
                'voyageur' => 'Tovo Rakotomalala',
                'tel' => '+261 33 07 44 19',
                'personnes' => 5,
                'dans' => 140,
                'nuits' => 4,
                'statut' => BookingStatus::Pending,
                'heures' => 29,
            ],
            [
                'reference' => 'VY-5MB7W',
                'slug' => 'villa-ambatoloaka',
                'voyageur' => 'Anne-Sophie Berger',
                'tel' => '+32 470 88 12 33',
                'personnes' => 3,
                'dans' => 170,
                'nuits' => 7,
                'statut' => BookingStatus::Accepted,
                'heures' => 0,
            ],
            [
                'reference' => 'VY-2HJ6V',
                'slug' => 'lodge-andasibe',
                'voyageur' => 'Fanja Rasoanaivo',
                'tel' => '+261 32 41 90 76',
                'personnes' => 2,
                'dans' => 185,
                'nuits' => 3,
                'statut' => BookingStatus::Accepted,
                'heures' => 0,
            ],
        ];
    }
}
