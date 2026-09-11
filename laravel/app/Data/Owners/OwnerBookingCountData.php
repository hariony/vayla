<?php

namespace App\Data\Owners;

use Spatie\LaravelData\Data;

/** Une pastille de filtre de l'historique : l'état, son libellé, combien. */
final class OwnerBookingCountData extends Data
{
    public function __construct(
        public readonly string $value,
        public readonly string $label,
        public readonly int $n,
    ) {}
}
