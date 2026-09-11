<?php

namespace App\Data\Listings;

use Spatie\LaravelData\Data;

/** Une rubrique d'équipements repliable — cent deux cases en une liste sont illisibles. */
final class AmenityChoiceGroupData extends Data
{
    /** @param  list<AmenityOptionData>  $amenities */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly array $amenities,
    ) {}
}
