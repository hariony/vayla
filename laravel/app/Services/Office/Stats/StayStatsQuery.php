<?php

namespace App\Services\Office\Stats;

use App\Contracts\Repositories\OfficeStatsRepositoryInterface;
use App\Data\Office\Stats\StayFiguresData;
use App\Data\Office\Stats\StayStatsPageData;
use App\DTOs\Office\StatsFilterDto;

/**
 * Statistiques › Séjours et commission : les séjours effectués — les seuls qui
 * se facturent — et la commission qu'ils portent. Les deux se rangent **au
 * mois du départ**, la règle de la facture ; ils vivent donc sur le même
 * écran, et les chiffres de l'un expliquent ceux de l'autre.
 */
final class StayStatsQuery
{
    public function __construct(
        private OfficeStatsRepositoryInterface $stats,
        private StatsFrame $cadre,
        private ActivityStats $activite,
        private CommissionStats $commission,
    ) {}

    public function page(StatsFilterDto $filtre): StayStatsPageData
    {
        $grille = MonthGrid::sur($filtre->periode);
        $sejours = $this->stats->sejoursDepuis($grille->debut, $filtre->avecDemo);

        return new StayStatsPageData(
            cadre: $this->cadre->data($grille, $filtre),
            chiffres: new StayFiguresData($sejours->count(), (int) $sejours->sum('nights')),
            sejours: $this->activite->sejours($grille, $sejours),
            commission: $this->commission->rapport($grille, $sejours, $this->stats->reglementsDepuis($grille->debut, $filtre->avecDemo)),
        );
    }
}
