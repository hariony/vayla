<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/** La fourchette de prix à la nuit, en ariary. Nulle quand il n'y a aucun logement. */
final class PriceRangeData extends Data
{
    public function __construct(
        public readonly ?int $min,
        public readonly ?int $max,
    ) {}
}
