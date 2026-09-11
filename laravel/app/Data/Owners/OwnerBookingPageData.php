<?php

namespace App\Data\Owners;

use App\Data\Bookings\MessageData;
use Spatie\LaravelData\Data;

/** Les props de `Owner/Bookings/Show` : la réservation, et son fil lu du côté du propriétaire. */
final class OwnerBookingPageData extends Data
{
    /** @param  list<MessageData>  $messages */
    public function __construct(
        public readonly OwnerBookingData $booking,
        public readonly array $messages,
    ) {}
}
