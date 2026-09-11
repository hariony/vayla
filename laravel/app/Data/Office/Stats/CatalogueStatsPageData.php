<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/** Les props de `Office/Stats/Catalogue`. */
final class CatalogueStatsPageData extends Data
{
    /**
     * @param  list<SeriesData>  $inscriptions
     * @param  list<BarData>  $statuts
     * @param  list<BarData>  $niveaux
     */
    public function __construct(
        public readonly StatsFrameData $cadre,
        public readonly CatalogueFiguresData $chiffres,
        public readonly array $inscriptions,
        public readonly array $statuts,
        public readonly array $niveaux,
    ) {}
}
