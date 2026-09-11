<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/** Un mois à venir sur la grille d'un logement ou d'une destination : sa saison, et ce qu'elle veut dire. */
final class SeasonMonthData extends Data
{
    public function __construct(
        public readonly string $month,
        public readonly string $kind,
        public readonly string $label,
        public readonly string $icon,
        public readonly ?string $note,
        public readonly bool $warning,
        public readonly bool $best,
    ) {}
}
