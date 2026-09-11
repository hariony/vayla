<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/** Les chiffres des demandes sur la période. `null` : rien à mesurer, pas zéro. */
final class RequestFiguresData extends Data
{
    public function __construct(
        public readonly int $demandes,
        public readonly ?float $tauxReponse,
        public readonly ?float $delaiMedian,
    ) {}
}
