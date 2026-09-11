<?php

namespace App\Data\StayRequests;

use App\Data\Office\ListFilterData;
use App\Data\Office\PaginationData;
use App\Data\Office\TabData;
use Spatie\LaravelData\Data;

/** Les props de `Office/Demandes/Index`. */
final class StayRequestQueuePageData extends Data
{
    /**
     * @param  array<int, StayRequestItemData>  $demandes
     * @param  array<int, TabData>  $onglets
     */
    public function __construct(
        public readonly array $demandes,
        public readonly PaginationData $meta,
        public readonly array $onglets,
        public readonly ListFilterData $filtre,
    ) {}
}
