<?php

namespace App\Data\Office\Listings;

use Spatie\LaravelData\Data;

/** Ce que le logement accueille. Deux chiffres décident — capacité et chambres. */
final class CapacityData extends Data
{
    public function __construct(
        public readonly int $guests,
        public readonly int $bedrooms,
        public readonly int $beds,
        public readonly int $bathrooms,
        public readonly ?int $surface,
    ) {}
}
