<?php

namespace App\Data\Office\Bookings;

use App\Data\Office\ListFilterData;
use App\Data\Office\PaginatedData;
use Spatie\LaravelData\Data;

/** Les props de `Office/Bookings/Index`. */
final class BookingQueuePageData extends Data
{
    public function __construct(
        public readonly PaginatedData $reservations,
        public readonly array $onglets,
        public readonly ListFilterData $filtre,
    ) {}
}
