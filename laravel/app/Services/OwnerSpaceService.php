<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Owner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * L'espace propriétaire.
 *
 * **Un seul écran, et une seule chose à y faire.** Le propriétaire n'ouvre
 * pas Vayla par curiosité : il l'ouvre parce qu'un message lui dit qu'une
 * demande attend. Le tableau de bord est donc ordonné par urgence, pas par
 * catégorie — ce qui exige une réponse d'abord, ce qui se lit ensuite.
 *
 * Quatre blocs, et l'ordre n'est pas négociable :
 *
 * 1. **Les demandes à répondre.** Elles bloquent le calendrier et expirent
 *    seules. Ce sont les seules lignes de l'écran qui portent un bouton.
 * 2. **Les séjours à venir**, pour savoir qui arrive.
 * 3. **Les logements**, avec leur niveau de vérification.
 * 4. **La facture du mois passé**, avec ce qu'il doit et par quel numéro.
 *
 * **Chaque demande affiche la commission à côté du total.** Un propriétaire
 * qui découvre le montant sur la facture de fin de mois se sent piégé — et il
 * a raison. Le taux est figé à la réservation, donc connu dès l'instant où on
 * lui demande de répondre : le lui cacher jusqu'à la facture ne servirait
 * qu'à obtenir un « oui » moins éclairé.
 */
class OwnerSpaceService
{
    public function __construct(
        private InvoiceService $invoices,
    ) {}

    /** @return array<string, mixed> */
    public function dashboard(Owner $owner): array
    {
        $bookings = $owner->bookings;

        return [
            'owner' => [
                'name' => $owner->name,
                'city' => $owner->city,
                'phone' => $owner->phone,
                'mobileMoney' => $owner->mobile_money,
                'operator' => $owner->mobile_money_operator,
            ],
            'pending' => $this->demandes($bookings, $owner),
            'upcoming' => $this->aVenir($bookings, $owner),
            'listings' => $this->logements($owner),
            'invoice' => $this->invoices->forOwner($owner),
            'demo' => (bool) config('vayla.demo'),
        ];
    }

    /**
     * Les demandes en attente de réponse.
     *
     * Une demande dont le délai a coulé mais que la commande horaire n'a pas
     * encore vue **n'est pas affichée comme répondable** : proposer un bouton
     * « Accepter » sur des nuits déjà rendues au calendrier ferait accepter un
     * séjour que Vayla ne peut plus garantir.
     *
     * @param  Collection<int, Booking>  $bookings
     * @return array<int, array<string, mixed>>
     */
    private function demandes($bookings, Owner $owner): array
    {
        return $bookings
            ->filter(fn (Booking $b) => $b->status === BookingStatus::Pending)
            ->filter(fn (Booking $b) => $b->hold_expires_at === null || $b->hold_expires_at->isFuture())
            ->sortBy('hold_expires_at')
            ->map(fn (Booking $b) => $this->ligne($b, $owner) + [
                // Les heures restantes, pas la date d'expiration : « il vous
                // reste 41 h » se comprend sans calcul, « expire le 5 à
                // 14 h 12 » demande de savoir quelle heure il est.
                'hoursLeft' => $b->hold_expires_at
                    ? max(0, (int) floor(Carbon::now()->diffInHours($b->hold_expires_at, false)))
                    : null,
                'message' => $b->message,
                'guests' => $b->guests,
                'phone' => $b->traveller_phone,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     * @return array<int, array<string, mixed>>
     */
    private function aVenir($bookings, Owner $owner): array
    {
        return $bookings
            ->filter(fn (Booking $b) => $b->status === BookingStatus::Accepted)
            ->filter(fn (Booking $b) => $b->departure->greaterThanOrEqualTo(Carbon::today()))
            ->sortBy('arrival')
            ->map(fn (Booking $b) => $this->ligne($b, $owner) + [
                'guests' => $b->guests,
                'phone' => $b->traveller_phone,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function logements(Owner $owner): array
    {
        return $owner->listings->map(fn (Listing $l) => [
            'slug' => $l->slug,
            'title' => $l->title,
            'place' => $l->destination?->name,
            // L'objet complet, pas la clé : le `srcset` du front a besoin
            // de la largeur réellement disponible.
            'photo' => $l->photos->first()
                ? ['key' => $l->photos->first()->key, 'width' => $l->photos->first()->width]
                : null,
            'price' => $l->price,
            'trust' => $l->trust_level->value,
            'trustName' => $l->trust_level->label(),
            // Le nombre de périodes fermées à venir : c'est ce qui donne au
            // bouton « calendrier » une raison d'être pressé. Un bouton dont
            // on ne sait pas ce qu'il y a derrière ne se presse pas.
            'blocked' => $l->unavailabilities
                ->filter(fn ($u) => $u->ends_on->greaterThanOrEqualTo(Carbon::today()))
                ->count(),
        ])->all();
    }

    /**
     * Le tronc commun d'une ligne de réservation. La commission y figure
     * toujours : c'est le seul endroit où le propriétaire la voit **avant**
     * de s'engager.
     *
     * @return array<string, mixed>
     */
    private function ligne(Booking $b, Owner $owner): array
    {
        return [
            'reference' => $b->reference,
            'listing' => $owner->listings->firstWhere('id', $b->listing_id)?->title ?? '—',
            'traveller' => $b->traveller,
            'arrival' => $b->arrival->toDateString(),
            'departure' => $b->departure->toDateString(),
            'nights' => $b->nights,
            'total' => $b->total,
            'commission' => $b->commission(),
            'rate' => (float) $b->commission_rate,
        ];
    }
}
