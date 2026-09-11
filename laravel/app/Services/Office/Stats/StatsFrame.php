<?php

namespace App\Services\Office\Stats;

use App\Contracts\Repositories\OfficeStatsRepositoryInterface;
use App\Data\Office\Stats\StatsDemoData;
use App\Data\Office\Stats\StatsFrameData;
use App\Data\Office\Stats\StatsMonthsData;
use App\Data\Office\Stats\StatsPeriodData;
use App\DTOs\Office\StatsFilterDto;
use App\Enums\StatsPeriod;

/**
 * Le cadre commun aux trois écrans de statistiques : la période choisie, les
 * mois qu'elle couvre, et **si la démonstration est dans les chiffres** —
 * chaque écran le dit, parce que des courbes nourries de réservations fictives
 * ne doivent jamais passer pour l'activité réelle.
 */
final class StatsFrame
{
    public function __construct(
        private OfficeStatsRepositoryInterface $stats,
    ) {}

    public function data(MonthGrid $grille, StatsFilterDto $filtre): StatsFrameData
    {
        return new StatsFrameData(
            periode: new StatsPeriodData($filtre->periode->value, StatsPeriod::choix(), $grille->debut->translatedFormat('F Y')),
            demo: new StatsDemoData($filtre->avecDemo, $this->stats->demoPresente()),
            mois: new StatsMonthsData($grille->courts(), $grille->longs()),
        );
    }
}
