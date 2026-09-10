<?php

namespace App\Services;

use App\Data\BookingData;
use App\Data\ListingData;
use App\Data\StayRulesData;
use App\Exceptions\ListingNotFoundException;
use App\Models\Booking;
use App\Models\Listing;
use App\Repositories\Contracts\ListingRepositoryInterface;

/**
 * Orchestrateur des écrans de réservation.
 *
 * Il sert les props et rien d'autre : les règles métier — chevauchement,
 * séjour minimum, capacité, expiration — vivent dans BookingService, parce
 * que l'application mobile devra les appliquer aussi et qu'une règle
 * dupliquée finit par diverger.
 */
class BookingPageService
{
    public function __construct(
        private ListingRepositoryInterface $listings,
        private BookingService $bookings,
        private AvailabilityService $availability,
        private PhotoService $photos,
    ) {}

    /** @return array<string, mixed> */
    public function form(string $slug, ?string $arrivee, ?string $depart, ?int $voyageurs): array
    {
        $listing = $this->listing($slug);

        return [
            'listing' => ListingData::fromModel($listing),
            'calendar' => $this->availability->calendar($listing),
            'rules' => StayRulesData::fromModel($listing),
            'prefill' => [
                'arrival' => $arrivee,
                'departure' => $depart,
                'guests' => $voyageurs,
            ],
            'holdHours' => (int) config('vayla.booking.hold_hours'),
            'photos' => $this->photos->map(),
            'credits' => $this->photos->credits(),
        ];
    }

    /** @param array<string, mixed> $data */
    public function book(string $slug, array $data): Booking
    {
        return $this->bookings->book($this->listing($slug), $data);
    }

    /** @return array<string, mixed> */
    public function confirmed(string $reference): array
    {
        $booking = Booking::query()
            ->with(['listing.destination', 'listing.photos', 'listing.categories', 'listing.amenities', 'listing.owner'])
            ->where('reference', $reference)
            ->first()
            ?? throw new ListingNotFoundException($reference);

        return [
            'booking' => BookingData::fromModel($booking),
            'holdHours' => (int) config('vayla.booking.hold_hours'),
            'photos' => $this->photos->map(),
            'credits' => $this->photos->credits(),
        ];
    }

    private function listing(string $slug): Listing
    {
        return $this->listings->findBySlug($slug, (bool) config('vayla.demo'))
            ?? throw new ListingNotFoundException($slug);
    }
}
