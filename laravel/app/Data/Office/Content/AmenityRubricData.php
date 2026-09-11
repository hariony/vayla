<?php

namespace App\Data\Office\Content;

use Spatie\LaravelData\Data;

/** Une rubrique d'équipements (`AmenityGroup`) et ce qu'elle contient. */
final class AmenityRubricData extends Data
{
    /** @param  array<int, AmenityRowData>  $equipements */
    public function __construct(
        public readonly string $value,
        public readonly string $label,
        public readonly array $equipements,
    ) {}
}
