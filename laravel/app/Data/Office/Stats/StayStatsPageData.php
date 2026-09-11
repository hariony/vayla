<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/** Les props de `Office/Stats/Sejours` : les séjours effectués, et la commission qu'ils portent. */
final class StayStatsPageData extends Data
{
    /** @param  list<SeriesData>  $sejours */
    public function __construct(
        public readonly StatsFrameData $cadre,
        public readonly StayFiguresData $chiffres,
        public readonly array $sejours,
        public readonly CommissionData $commission,
    ) {}
}
