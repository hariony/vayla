<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/** Un segment du ruban de l'année (`SeasonRibbon`) : janvier à décembre, dans l'ordre. */
final class SeasonYearMonthData extends Data
{
    public function __construct(
        public readonly int $n,
        public readonly string $month,
        public readonly string $initial,
        public readonly string $kind,
        public readonly string $label,
        public readonly ?string $note,
        public readonly bool $warning,
        public readonly bool $best,
    ) {}
}
