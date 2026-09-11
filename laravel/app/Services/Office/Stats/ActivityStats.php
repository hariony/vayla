<?php

namespace App\Services\Office\Stats;

use App\Contracts\Repositories\OfficeStatsRepositoryInterface;
use App\Data\Office\Stats\SeriesData;
use App\Models\Booking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Ce qui s'est passé : les séjours et les inscriptions. La commission a son
 * propre calcul (`CommissionStats`).
 *
 * **Un séjour se range au mois du départ** — c'est la règle de la facture, et
 * les deux écrans doivent tomber sur les mêmes séjours.
 */
final class ActivityStats
{
    public function __construct(
        private OfficeStatsRepositoryInterface $stats,
    ) {}

    /**
     * @param  Collection<int, Booking>  $sejours
     * @return list<SeriesData>
     */
    public function sejours(MonthGrid $grille, Collection $sejours): array
    {
        return [
            new SeriesData('sejours', 'Séjours effectués', 'encre', $grille->compter($sejours, $this->moisDeDepart(...))),
            new SeriesData('nuits', 'Nuits', 'terre', $grille->additionner($sejours, $this->moisDeDepart(...), fn (Booking $b) => $b->nights)),
        ];
    }

    /** @return list<SeriesData> au mois de la création */
    public function inscriptions(MonthGrid $grille, bool $avecDemo): array
    {
        $mois = fn (Carbon $date) => $date->format('Y-m');

        return [
            new SeriesData('voyageurs', 'Comptes voyageurs', 'encre', $grille->compter($this->stats->inscriptionsVoyageurs($grille->debut, $avecDemo), $mois)),
            new SeriesData('proprietaires', 'Propriétaires', 'terre', $grille->compter($this->stats->inscriptionsProprietaires($grille->debut, $avecDemo), $mois)),
            new SeriesData('annonces', 'Annonces créées', 'gris', $grille->compter($this->stats->annoncesCreees($grille->debut, $avecDemo), $mois)),
        ];
    }

    private function moisDeDepart(Booking $b): string
    {
        return $b->departure->format('Y-m');
    }
}
