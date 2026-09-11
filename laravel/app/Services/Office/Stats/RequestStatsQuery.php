<?php

namespace App\Services\Office\Stats;

use App\Contracts\Repositories\OfficeStatsRepositoryInterface;
use App\Data\Office\Stats\RequestFiguresData;
use App\Data\Office\Stats\RequestStatsPageData;
use App\DTOs\Office\StatsFilterDto;

/**
 * Statistiques › Demandes : ce que les voyageurs demandent, et comment les
 * propriétaires répondent. Une demande se range **au mois où elle a été
 * faite** ; le délai est une médiane, le taux ne compte que les demandes
 * tranchées (`RequestStats`).
 */
final class RequestStatsQuery
{
    public function __construct(
        private OfficeStatsRepositoryInterface $stats,
        private StatsFrame $cadre,
        private RequestStats $demandes,
    ) {}

    public function page(StatsFilterDto $filtre): RequestStatsPageData
    {
        $grille = MonthGrid::sur($filtre->periode);
        $demandes = $this->stats->demandesDepuis($grille->debut, $filtre->avecDemo);

        return new RequestStatsPageData(
            cadre: $this->cadre->data($grille, $filtre),
            chiffres: new RequestFiguresData(
                demandes: $demandes->count(),
                tauxReponse: $this->demandes->taux($demandes),
                delaiMedian: $this->demandes->delaiMedian($demandes),
            ),
            demandes: $this->demandes->parIssue($grille, $demandes),
            reponse: $this->demandes->reponse($grille, $demandes),
            delai: $this->demandes->delai($grille, $demandes),
            destinations: $this->demandes->parDestination($demandes),
        );
    }
}
