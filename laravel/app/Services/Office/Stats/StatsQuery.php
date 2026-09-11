<?php

namespace App\Services\Office\Stats;

use App\Contracts\Repositories\OfficeStatsRepositoryInterface;
use App\Data\Office\Stats\StatsDemoData;
use App\Data\Office\Stats\StatsFiguresData;
use App\Data\Office\Stats\StatsMonthsData;
use App\Data\Office\Stats\StatsPageData;
use App\Data\Office\Stats\StatsPeriodData;
use App\DTOs\Office\StatsFilterDto;
use App\Enums\StatsPeriod;
use Illuminate\Support\Collection;

/**
 * Les statistiques du back-office : des courbes, et **rien que des comptes
 * définis**.
 *
 * Sur un produit dont toute la promesse est la vérification, un chiffre
 * invérifiable est le premier mensonge. Chaque série dit donc ce qu'elle
 * compte, et à quelle date elle le range — voir `RequestStats` et
 * `ActivityStats`.
 *
 * **Les données de démonstration se séparent d'un réglage**, et l'écran dit
 * quand elles sont incluses : des courbes nourries de réservations fictives
 * ne doivent jamais passer pour l'activité réelle.
 *
 * Le jour où les volumes ne tiendront plus en mémoire, ce sera une vue
 * matérialisée — et `OfficeStatsRepository` sera le seul fichier à changer.
 */
final class StatsQuery
{
    public function __construct(
        private OfficeStatsRepositoryInterface $stats,
        private RequestStats $demandes,
        private ActivityStats $activite,
        private CommissionStats $commission,
        private CatalogueStats $catalogue,
    ) {}

    public function page(StatsFilterDto $filtre): StatsPageData
    {
        $grille = MonthGrid::sur($filtre->periode);
        $demandes = $this->stats->demandesDepuis($grille->debut, $filtre->avecDemo);
        $sejours = $this->stats->sejoursDepuis($grille->debut, $filtre->avecDemo);
        $commission = $this->commission->rapport($grille, $sejours, $this->stats->reglementsDepuis($grille->debut, $filtre->avecDemo));

        return new StatsPageData(
            periode: new StatsPeriodData($filtre->periode->value, StatsPeriod::choix(), $grille->debut->translatedFormat('F Y')),
            demo: new StatsDemoData($filtre->avecDemo, $this->stats->demoPresente()),
            mois: new StatsMonthsData($grille->courts(), $grille->longs()),
            chiffres: $this->chiffres($demandes, $sejours, $commission->chiffres->facturee, $commission->chiffres->reglee),
            demandes: $this->demandes->parIssue($grille, $demandes),
            reponse: $this->demandes->reponse($grille, $demandes),
            delai: $this->demandes->delai($grille, $demandes),
            sejours: $this->activite->sejours($grille, $sejours),
            commission: $commission,
            inscriptions: $this->activite->inscriptions($grille, $filtre->avecDemo),
            destinations: $this->demandes->parDestination($demandes),
            statuts: $this->catalogue->statuts($filtre->avecDemo),
            niveaux: $this->catalogue->niveaux($filtre->avecDemo),
        );
    }

    private function chiffres(Collection $demandes, Collection $sejours, int $commission, int $reglee): StatsFiguresData
    {
        return new StatsFiguresData(
            demandes: $demandes->count(),
            tauxReponse: $this->demandes->taux($demandes),
            delaiMedian: $this->demandes->delaiMedian($demandes),
            sejours: $sejours->count(),
            nuits: (int) $sejours->sum('nights'),
            volume: (int) $sejours->sum('total'),
            commission: $commission,
            reglee: $reglee,
        );
    }
}
