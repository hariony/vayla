<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/** Une série d'un graphique : une valeur par mois, `null` là où le mois n'a rien à dire. */
final class SeriesData extends Data
{
    /** @param  list<int|float|null>  $valeurs */
    public function __construct(
        public readonly string $cle,
        public readonly string $label,
        public readonly string $teinte,
        public readonly array $valeurs,
    ) {}
}
