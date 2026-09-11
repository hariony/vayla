<?php

namespace App\Data\Listings;

use Spatie\LaravelData\Data;

/** Un équipement coché sur une fiche, et s'il est mis en avant — c'est `highlight` qui fait les arguments de la carte. */
final class AmenityPickData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly bool $highlight,
    ) {}
}
