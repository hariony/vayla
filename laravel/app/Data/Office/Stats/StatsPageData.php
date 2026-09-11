<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/** Les props de `Office/Stats/Index`. */
final class StatsPageData extends Data
{
    /**
     * @param  list<SeriesData>  $demandes
     * @param  list<SeriesData>  $reponse
     * @param  list<SeriesData>  $delai
     * @param  list<SeriesData>  $sejours
     * @param  list<SeriesData>  $inscriptions
     * @param  list<BarData>  $destinations
     * @param  list<BarData>  $statuts
     * @param  list<BarData>  $niveaux
     */
    public function __construct(
        public readonly StatsPeriodData $periode,
        public readonly StatsDemoData $demo,
        public readonly StatsMonthsData $mois,
        public readonly StatsFiguresData $chiffres,
        public readonly array $demandes,
        public readonly array $reponse,
        public readonly array $delai,
        public readonly array $sejours,
        public readonly CommissionData $commission,
        public readonly array $inscriptions,
        public readonly array $destinations,
        public readonly array $statuts,
        public readonly array $niveaux,
    ) {}
}
