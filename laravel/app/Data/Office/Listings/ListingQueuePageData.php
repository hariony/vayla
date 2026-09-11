<?php

namespace App\Data\Office\Listings;

use App\Data\Office\ListFilterData;
use App\Data\Office\PaginatedData;
use Spatie\LaravelData\Data;

/** Les props de `Office/Listings/Index`. */
final class ListingQueuePageData extends Data
{
    public function __construct(
        public readonly PaginatedData $annonces,
        public readonly array $onglets,
        public readonly ListFilterData $filtre,
    ) {}
}
