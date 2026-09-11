<?php

namespace App\Data\Office\Owners;

use App\Data\Office\ListFilterData;
use App\Data\Office\PaginatedData;
use Spatie\LaravelData\Data;

/** Les props de `Office/Owners/Index`. */
final class OwnerQueuePageData extends Data
{
    public function __construct(
        public readonly PaginatedData $proprietaires,
        public readonly array $onglets,
        public readonly ListFilterData $filtre,
    ) {}
}
