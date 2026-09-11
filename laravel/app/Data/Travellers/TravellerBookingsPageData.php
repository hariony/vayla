<?php

namespace App\Data\Travellers;

use Spatie\LaravelData\Data;

/** Les props de `Auth/Bookings`. */
final class TravellerBookingsPageData extends Data
{
    /** @param  list<TravellerBookingData>  $bookings */
    public function __construct(
        public readonly TravellerIdentityData $traveller,
        public readonly array $bookings,
    ) {}
}
