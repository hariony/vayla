<?php

namespace App\Data\Owners;

use App\Data\ListingCalendarData;
use App\Data\OptionData;
use Spatie\LaravelData\Data;

/**
 * Les props de `Owner/Calendar`. Le calendrier y est **sans bornes de séjour** :
 * elles encadrent ce qu'un voyageur réserve, pas ce qu'un propriétaire ferme.
 */
final class OwnerCalendarPageData extends Data
{
    /**
     * @param  list<DeclaredPeriodData>  $declared
     * @param  list<BookedPeriodData>  $booked
     * @param  list<OptionData>  $reasons
     */
    public function __construct(
        public readonly CalendarListingData $listing,
        public readonly ListingCalendarData $calendar,
        public readonly array $declared,
        public readonly array $booked,
        public readonly array $reasons,
    ) {}
}
