<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/** Les props de `Office/Stats/Demandes`. */
final class RequestStatsPageData extends Data
{
    /**
     * @param  list<SeriesData>  $demandes  par issue
     * @param  list<SeriesData>  $reponse
     * @param  list<SeriesData>  $delai
     * @param  list<BarData>  $destinations
     */
    public function __construct(
        public readonly StatsFrameData $cadre,
        public readonly RequestFiguresData $chiffres,
        public readonly array $demandes,
        public readonly array $reponse,
        public readonly array $delai,
        public readonly array $destinations,
    ) {}
}
