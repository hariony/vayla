<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/** La période affichée, les choix possibles, et le mois où elle commence (« octobre 2025 »). */
final class StatsPeriodData extends Data
{
    /** @param  list<int>  $choix */
    public function __construct(
        public readonly int $mois,
        public readonly array $choix,
        public readonly string $debut,
    ) {}
}
