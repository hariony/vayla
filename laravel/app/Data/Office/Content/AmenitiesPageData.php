<?php

namespace App\Data\Office\Content;

use Spatie\LaravelData\Data;

/** Les props de `Office/Amenities/Index`. */
final class AmenitiesPageData extends Data
{
    /**
     * @param  array<int, AmenityRubricData>  $rubriques
     * @param  array<int, string>  $icones  seulement les pictogrammes déjà dessinés
     */
    public function __construct(
        public readonly array $rubriques,
        public readonly array $icones,
        public readonly int $total,
    ) {}
}
