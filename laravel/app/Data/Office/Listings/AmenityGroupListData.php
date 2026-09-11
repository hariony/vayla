<?php

namespace App\Data\Office\Listings;

use Spatie\LaravelData\Data;

/** Une rubrique d'équipements déclarés. */
final class AmenityGroupListData extends Data
{
    public function __construct(
        public readonly string $label,
        public readonly array $items,
    ) {}
}
