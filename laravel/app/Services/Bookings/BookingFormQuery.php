<?php

namespace App\Services\Bookings;

use App\Data\ListingData;
use App\Data\Pages\BookingFormPageData;
use App\Data\Pages\BookingPrefillData;
use App\Data\Pages\TravellerPrefillData;
use App\Data\StayRulesData;
use App\DTOs\Bookings\BookingPrefillDto;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\PhotoService;

/**
 * Le formulaire de demande de séjour. Connecté, le voyageur y retrouve ses
 * coordonnées ; **le compte n'est toujours pas exigé** — sans lui, les champs
 * sont simplement vides.
 */
final class BookingFormQuery
{
    public function __construct(
        private BookableListings $annonces,
        private AvailabilityService $availability,
        private PhotoService $photos,
    ) {}

    public function page(string $slug, BookingPrefillDto $prefill, ?User $compte): BookingFormPageData
    {
        $listing = $this->annonces->trouver($slug);

        return new BookingFormPageData(
            listing: ListingData::fromModel($listing),
            calendar: $this->availability->calendar($listing),
            rules: StayRulesData::fromModel($listing),
            prefill: BookingPrefillData::depuis($prefill),
            voyageur: $compte ? TravellerPrefillData::fromModel($compte) : null,
            holdHours: (int) config('vayla.booking.hold_hours'),
            photos: $this->photos->map(),
            credits: $this->photos->credits(),
        );
    }
}
