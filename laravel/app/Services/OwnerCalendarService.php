<?php

namespace App\Services;

use App\Contracts\Repositories\UnavailabilityRepositoryInterface;
use App\Data\Owners\BookedPeriodData;
use App\Data\Owners\CalendarListingData;
use App\Data\Owners\DeclaredPeriodData;
use App\Data\Owners\OwnerCalendarPageData;
use App\Data\SejourData;
use App\Enums\BlockReason;
use App\Exceptions\CalendarRefusedException;
use App\Exceptions\ListingNotFoundException;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Owner;
use App\Models\Unavailability;
use App\Services\Owners\OwnerSpace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
        private OwnerSpace $portee,
    ) {}

    /** @throws ListingNotFoundException */
    public function page(Owner $owner, string $slug): OwnerCalendarPageData
    {
        $listing = $this->portee->logement($owner, $slug);

        return new OwnerCalendarPageData(
            listing: CalendarListingData::fromModel($listing),
            // Les bornes de séjour du logement sont **retirées** : elles
            // encadrent ce qu'un voyageur peut réserver, pas ce que le
            // propriétaire peut fermer. Un logement qui se loue au minimum
            // trois nuits doit pouvoir être bloqué une seule soirée.
            calendar: $this->availability->calendar($listing)->sansBornes(),
            declared: $this->periodes->aVenir($listing)->map(fn (Unavailability $u) => DeclaredPeriodData::fromModel($u))->values()->all(),
            booked: $this->reservees($listing)->map(fn (Booking $b) => BookedPeriodData::fromModel($b))->values()->all(),
            reasons: BlockReason::options(),
        );
    }

    /** @throws ListingNotFoundException|CalendarRefusedException */
    public function bloquer(Owner $owner, string $slug, SejourData $sejour, BlockReason $motif): Unavailability
    {
        $listing = $this->portee->logement($owner, $slug);

        $this->refuserSiReserve($listing, $sejour);
        $this->refuserSiDejaBloque($listing, $sejour);

        return $this->periodes->bloquer($listing, $sejour, $motif);
    }

    /** @throws ListingNotFoundException|CalendarRefusedException */
    public function liberer(Owner $owner, string $slug, int $id): void
    {
        if (! $this->periodes->liberer($this->portee->logement($owner, $slug), $id)) {
            throw new CalendarRefusedException("Cette période n'existe plus.");
        }
    }

    /** @return Collection<int, Booking> les réservations qui tiennent des nuits à venir */
    private function reservees(Listing $listing): Collection
    {
        return $this->bloquantes($listing)
            ->filter(fn (Booking $b) => $b->departure->greaterThanOrEqualTo(Carbon::today()))
            ->sortBy('arrival');
    }

    /** Une demande dont le délai a coulé ne tient plus rien, même avant le passage de la commande. */
    private function bloquantes(Listing $listing): Collection
    {
        return $listing->bookings->filter(fn (Booking $b) => $b->status->blocksDates()
            && ! ($b->hold_expires_at !== null && $b->hold_expires_at->isPast()));
    }

    private function refuserSiReserve(Listing $listing, SejourData $sejour): void
    {
        $conflit = $this->bloquantes($listing)
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
