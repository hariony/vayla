<?php

namespace App\Data\Pages;

use App\Data\ListingCalendarData;
use App\Data\ListingData;
use App\Data\PhotoData;
use App\Data\StayRulesData;
use Spatie\LaravelData\Data;

/**
 * Les props de `Bookings/Create`. `voyageur` est nul pour un visiteur sans
 * compte : **demander un séjour ne réclame toujours aucun compte**, il saisit
 * simplement ses coordonnées.
 */
final class BookingFormPageData extends Data
{
    /**
     * @param  array<string, PhotoData>  $photos
     * @param  list<PhotoData>  $credits
     */
    public function __construct(
        public readonly ListingData $listing,
        public readonly ListingCalendarData $calendar,
        public readonly StayRulesData $rules,
        public readonly BookingPrefillData $prefill,
        public readonly ?TravellerPrefillData $voyageur,
        public readonly int $holdHours,
        public readonly array $photos,
        public readonly array $credits,
    ) {}
}
