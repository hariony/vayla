<?php

namespace App\Services;

use App\Data\SejourData;
use App\Enums\BlockReason;
use App\Enums\BookingStatus;
use App\Exceptions\CalendarRefusedException;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Unavailability;
use App\Repositories\Contracts\UnavailabilityRepositoryInterface;

/**
 * Le calendrier vu du côté du propriétaire.
 *
 * C'est la contrepartie de « une nuit est libre tant qu'aucune ligne ne la
 * couvre » : sans un endroit où déclarer ses périodes, un propriétaire qui
 * loue aussi par WhatsApp — c'est-à-dire tous, et ça durera — reçoit des
 * demandes sur des nuits déjà vendues, et n'a d'autre choix que de les
 * refuser une par une. Refuser abîme la relation avec le voyageur, fait
 * baisser la crédibilité de Vayla, et ne corrige rien : la nuit suivante,
 * une autre demande arrive.
 *
 * Trois règles tiennent l'écran :
 *
 * 1. **Le propriétaire pose une arrivée et un départ**, comme un voyageur, et
 *    c'est `SejourData` qui en tire la dernière nuit. La soustraction du jour
 *    de départ n'est écrite qu'à un seul endroit du dépôt ; la refaire ici
 *    aurait donné une quatrième écriture d'une règle qui décide du prix.
 * 2. **Deux sources d'occupation, un seul calendrier, deux traitements.** Une
 *    période déclarée se libère d'un bouton ; les nuits d'une réservation ne
 *    se libèrent pas depuis le calendrier — on refuse ou on annule la
 *    réservation, ce qui prévient le voyageur. Un bouton « Libérer » sur des
 *    nuits vendues ferait disparaître un séjour sans que personne ne
 *    l'apprenne.
 * 3. **Un refus dit quelles nuits bloquent, et pourquoi.** « Impossible » sur
 *    un téléphone, à quelqu'un qui n'a jamais rempli de formulaire, n'est pas
 *    un message : c'est une impasse.
 */
class OwnerCalendarService
{
    public function __construct(
        private UnavailabilityRepositoryInterface $periodes,
        private AvailabilityService $availability,
    ) {}

    /**
     * Ce que l'écran affiche pour un logement.
     *
     * @return array<string, mixed>
     */
    public function payload(Listing $listing): array
    {
        return [
            'listing' => [
                'slug' => $listing->slug,
                'title' => $listing->title,
                'place' => $listing->destination?->name,
            ],
            // Les bornes de séjour du logement sont **retirées** : elles
            // encadrent ce qu'un voyageur peut réserver, pas ce que le
            // propriétaire peut fermer. Un logement qui se loue au minimum
            // trois nuits doit pouvoir être bloqué une seule soirée.
            'calendar' => ['minNights' => 1, 'maxNights' => null] + $this->availability->calendar($listing),
            'declared' => $this->declarees($listing),
            'booked' => $this->reservees($listing),
            'reasons' => BlockReason::options(),
        ];
    }

    /**
     * Les périodes que le propriétaire a posées lui-même — les seules qu'il
     * puisse reprendre.
     *
     * @return array<int, array<string, mixed>>
     */
    private function declarees(Listing $listing): array
    {
        return $this->periodes->aVenir($listing)
            ->map(fn (Unavailability $u) => [
                'id' => $u->id,
                'from' => $u->starts_on->toDateString(),
                // La dernière nuit occupée, telle qu'elle est stockée. Le
                // front affiche à côté le jour de libération : c'est là que
                // se joue toute la compréhension de la règle.
                'to' => $u->ends_on->toDateString(),
                'nights' => (int) $u->starts_on->diffInDays($u->ends_on) + 1,
                'reason' => $u->reason?->value,
                'reasonLabel' => $u->reason?->label(),
            ])
            ->all();
    }

    /**
     * Les nuits retirées par une réservation. Lecture seule, et le libellé
     * dit où aller pour les récupérer.
     *
     * @return array<int, array<string, mixed>>
     */
    private function reservees(Listing $listing): array
    {
        return $listing->bookings
            ->filter(fn (Booking $b) => $b->status->blocksDates()
                && ! ($b->hold_expires_at !== null && $b->hold_expires_at->isPast()))
            ->filter(fn (Booking $b) => $b->departure->greaterThanOrEqualTo(now()->startOfDay()))
            ->sortBy('arrival')
            ->map(fn (Booking $b) => [
                'reference' => $b->reference,
                'traveller' => $b->traveller,
                'from' => $b->arrival->toDateString(),
                'to' => $b->departure->copy()->subDay()->toDateString(),
                'nights' => $b->nights,
                'pending' => $b->status === BookingStatus::Pending,
            ])
            ->values()
            ->all();
    }

    /**
     * Ferme un séjour au calendrier.
     *
     * @throws CalendarRefusedException si les nuits sont déjà prises
     */
    public function bloquer(Listing $listing, SejourData $sejour, BlockReason $motif): Unavailability
    {
        $this->refuserSiReserve($listing, $sejour);
        $this->refuserSiDejaBloque($listing, $sejour);

        return $this->periodes->bloquer($listing, $sejour, $motif);
    }

    /**
     * Rouvre une période déclarée.
     *
     * Aucune confirmation n'est demandée côté écran, et c'est cohérent avec
     * le reste de l'espace : on confirme ce qui est irréversible. Rouvrir des
     * nuits se défait en trois clics ; refuser une demande, non.
     *
     * @throws CalendarRefusedException si la période n'est pas à ce logement
     */
    public function liberer(Listing $listing, int $id): void
    {
        if (! $this->periodes->liberer($listing, $id)) {
            throw new CalendarRefusedException("Cette période n'existe plus.");
        }
    }

    /**
     * Une réservation en cours interdit le blocage — et le message nomme la
     * réservation. Le propriétaire doit savoir **laquelle** répondre ou
     * annuler ; sans la référence, il revient au tableau de bord chercher.
     */
    private function refuserSiReserve(Listing $listing, SejourData $sejour): void
    {
        $conflit = $listing->bookings
            ->filter(fn (Booking $b) => $b->status->blocksDates()
                && ! ($b->hold_expires_at !== null && $b->hold_expires_at->isPast()))
            ->first(fn (Booking $b) => $sejour->couvre(
                $b->arrival->toDateString(),
                $b->departure->copy()->subDay()->toDateString(),
            ));

        if ($conflit) {
            throw new CalendarRefusedException(
                "Ces nuits portent la réservation {$conflit->reference} ({$conflit->traveller}). "
                .'Refusez-la ou annulez-la depuis votre espace avant de bloquer ces dates.'
            );
        }
    }

    /**
     * Deux périodes déclarées qui se chevauchent ne se fusionnent pas
     * silencieusement : le propriétaire croirait avoir ajouté une période,
     * et en retrouverait une autre. On refuse en disant laquelle gêne.
     */
    private function refuserSiDejaBloque(Listing $listing, SejourData $sejour): void
    {
        $conflit = $this->periodes->chevauchant($listing, $sejour)->first();

        if ($conflit) {
            $du = $conflit->starts_on->format('d/m/Y');
            $au = $conflit->ends_on->format('d/m/Y');

            throw new CalendarRefusedException(
                "Ces nuits sont déjà bloquées : une période va du {$du} au {$au} (dernière nuit). "
                .'Libérez-la d’abord si vous voulez la remplacer.'
            );
        }
    }
}
