<?php

namespace App\Data\Office\Bookings;

use App\Data\Office\OwnerCardData;
use Spatie\LaravelData\Data;

/** Les props de `Office/Bookings/Show`. `messages` garde la forme du fil (`ConversationService`, lot 3). */
final class BookingDetailPageData extends Data
{
    public function __construct(
        public readonly BookingDetailData $reservation,
        public readonly ?OwnerCardData $proprietaire,
        public readonly array $messages,
        public readonly array $journal,
    ) {}
}
