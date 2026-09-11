<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/** Ce que les trois écrans de statistiques partagent : la période, la démonstration, les mois. */
final class StatsFrameData extends Data
{
    public function __construct(
        public readonly StatsPeriodData $periode,
        public readonly StatsDemoData $demo,
        public readonly StatsMonthsData $mois,
    ) {}
}
