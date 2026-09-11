<?php

namespace App\Data\Office\Journal;

use App\Data\Office\ListFilterData;
use App\Data\Office\PaginatedData;
use Spatie\LaravelData\Data;

/** Les props de `Office/Journal/Index`. */
final class JournalPageData extends Data
{
    public function __construct(
        public readonly PaginatedData $lignes,
        public readonly array $onglets,
        public readonly ListFilterData $filtre,
    ) {}
}
