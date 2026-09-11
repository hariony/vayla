<?php

namespace App\Data\Bookings;

use Spatie\LaravelData\Data;

/** Les props d'une boîte — `Owner/Messages` comme `Auth/Messages` : une ligne par réservation qui porte un fil. */
final class InboxPageData extends Data
{
    /** @param  list<ConversationData>  $conversations */
    public function __construct(
        public readonly array $conversations,
    ) {}
}
