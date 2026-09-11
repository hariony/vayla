<?php

namespace App\Services\Owners;

use App\Contracts\Invoices\InvoiceCalculator;
use App\Contracts\Repositories\OwnerSpaceRepositoryInterface;
use App\Data\Owners\DashboardBookingData;
use App\Data\Owners\DashboardListingData;
use App\Data\Owners\OwnerDashboardPageData;
use App\Data\Owners\OwnerSummaryData;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Owner;
use App\Services\Support\DemoMode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Le tableau de bord du propriétaire, **ordonné par urgence, pas par
 * catégorie** : il n'ouvre pas Vayla par curiosité, il l'ouvre parce qu'un
 * message lui dit qu'une demande attend. Les demandes viennent donc en
 * premier — celle qui expire le plus tôt en tête —, puis les séjours à venir,
 * les logements, la facture.
 */
final class OwnerDashboardQuery
{
    public function __construct(
        private OwnerSpaceRepositoryInterface $espace,
        private InvoiceCalculator $factures,
        private DemoMode $demo,
    ) {}

    public function page(Owner $owner): OwnerDashboardPageData
    {
        $reservations = $this->espace->reservations($owner);

        return new OwnerDashboardPageData(
            owner: OwnerSummaryData::fromModel($owner),
            pending: $this->demandes($reservations),
            upcoming: $this->aVenir($reservations),
            listings: $this->espace->annonces($owner)->map(fn (Listing $l) => DashboardListingData::fromModel($l))->values()->all(),
            invoice: $this->factures->forOwner($owner),
            demo: $this->demo->actif(),
        );
    }

    /** @return list<DashboardBookingData> les demandes encore ouvertes, la plus pressée en tête */
    private function demandes(Collection $reservations): array
    {
        return $reservations
            ->filter(fn (Booking $b) => $b->status === BookingStatus::Pending)
            ->filter(fn (Booking $b) => $b->hold_expires_at === null || $b->hold_expires_at->isFuture())
            ->sortBy('hold_expires_at')
            ->map(fn (Booking $b) => DashboardBookingData::fromModel($b, avecDelai: true))
            ->values()
            ->all();
    }

    /** @return list<DashboardBookingData> les séjours acceptés qui ne sont pas finis */
    private function aVenir(Collection $reservations): array
    {
        return $reservations
            ->filter(fn (Booking $b) => $b->status === BookingStatus::Accepted)
            ->filter(fn (Booking $b) => $b->departure->greaterThanOrEqualTo(Carbon::today()))
            ->sortBy('arrival')
            ->map(fn (Booking $b) => DashboardBookingData::fromModel($b))
            ->values()
            ->all();
    }
}
