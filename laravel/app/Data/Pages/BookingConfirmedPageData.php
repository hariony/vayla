<?php

namespace App\Data\Pages;

use App\Data\BookingData;
use App\Data\Bookings\MessageData;
use App\Data\PhotoData;
use Spatie\LaravelData\Data;

/** Les props de `Bookings/Confirmed` : la réservation, son délai de réponse, et son fil. */
final class BookingConfirmedPageData extends Data
{
    /**
     * @param  list<MessageData>  $messages
     * @param  array<string, PhotoData>  $photos
     * @param  list<PhotoData>  $credits
     */
    public function __construct(
        public readonly BookingData $booking,
        public readonly int $holdHours,
        public readonly array $messages,
        public readonly array $photos,
        public readonly array $credits,
    ) {}
}
