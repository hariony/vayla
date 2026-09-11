<?php

namespace App\DTOs\Office;

use App\Enums\StatsPeriod;

/** La vue demandée : la période, et si la démonstration y entre. */
final readonly class StatsFilterDto
{
    public function __construct(
        public StatsPeriod $periode,
        public bool $avecDemo,
    ) {}
}
