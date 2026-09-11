<?php

namespace App\Services\Office\Stats;

use App\Data\Office\Stats\CatalogueFiguresData;
use App\Data\Office\Stats\CatalogueStatsPageData;
use App\Data\Office\Stats\SeriesData;
use App\DTOs\Office\StatsFilterDto;

/**
 * Statistiques › Catalogue et inscriptions : ce qui s'ouvre au fil des mois
 * (comptes, propriétaires, annonces saisies), et le catalogue **à cet
 * instant** — la période ne s'applique pas à ce dernier.
 */
final class CatalogueStatsQuery
{
    public function __construct(
        private StatsFrame $cadre,
        private ActivityStats $activite,
        private CatalogueStats $catalogue,
    ) {}

    public function page(StatsFilterDto $filtre): CatalogueStatsPageData
    {
        $grille = MonthGrid::sur($filtre->periode);
        $inscriptions = $this->activite->inscriptions($grille, $filtre->avecDemo);
        $total = fn (string $cle) => (int) array_sum(collect($inscriptions)->first(fn (SeriesData $s) => $s->cle === $cle)?->valeurs ?? []);

        return new CatalogueStatsPageData(
            cadre: $this->cadre->data($grille, $filtre),
            chiffres: new CatalogueFiguresData($total('voyageurs'), $total('proprietaires'), $total('annonces')),
            inscriptions: $inscriptions,
            statuts: $this->catalogue->statuts($filtre->avecDemo),
            niveaux: $this->catalogue->niveaux($filtre->avecDemo),
        );
    }
}
