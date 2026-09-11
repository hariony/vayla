<?php

namespace App\Data\Office\Travellers;

use App\Data\Office\ListFilterData;
use App\Data\Office\PaginatedData;
use Spatie\LaravelData\Data;

/** Les props de `Office/Travellers/Index`. */
final class TravellersPageData extends Data
{
    public function __construct(
        public readonly PaginatedData $voyageurs,
        public readonly int $total,
        public readonly ListFilterData $filtre,
    ) {}
}
