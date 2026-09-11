<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/** Une barre d'une comparaison horizontale. */
final class BarData extends Data
{
    public function __construct(
        public readonly string $label,
        public readonly int $valeur,
        public readonly string $teinte = 'encre',
    ) {}
}
