<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/** Les séjours effectués sur la période, et leurs nuits. */
final class StayFiguresData extends Data
{
    public function __construct(
        public readonly int $sejours,
        public readonly int $nuits,
    ) {}
}
