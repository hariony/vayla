<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/** Les libellés des mois : courts sous l'axe, longs dans le relevé. */
final class StatsMonthsData extends Data
{
    /**
     * @param  list<string>  $courts
     * @param  list<string>  $longs
     */
    public function __construct(
        public readonly array $courts,
        public readonly array $longs,
    ) {}
}
