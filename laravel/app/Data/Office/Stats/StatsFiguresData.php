<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/** Les chiffres de tête, sur toute la période. `null` : rien à mesurer, pas zéro. */
final class StatsFiguresData extends Data
{
    public function __construct(
        public readonly int $demandes,
        public readonly ?float $tauxReponse,
        public readonly ?float $delaiMedian,
        public readonly int $sejours,
        public readonly int $nuits,
        public readonly int $volume,
        public readonly int $commission,
        public readonly int $reglee,
    ) {}
}
