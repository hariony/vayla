<?php

namespace App\Services\Office\Stats;

use App\Data\Office\Stats\BarData;
use App\Data\Office\Stats\SeriesData;
use App\Enums\BookingOutcome;
use App\Models\Booking;
use App\Services\Support\Median;
use Illuminate\Support\Collection;

/**
 * Les demandes, **rangées au mois où elles ont été faites** : leur issue, et
 * la réponse des propriétaires.
 *
 * **Le délai est une médiane, pas une moyenne** : un propriétaire qui répond
 * au bout de quarante-sept heures ne doit pas faire croire que tous répondent
 * en une journée, ni l'inverse. **Le taux ne compte que les demandes
 * tranchées** — une demande encore en attente n'a pas encore échoué.
 */
final class RequestStats
{
    /**
     * @param  Collection<int, Booking>  $demandes
     * @return list<SeriesData>
     */
    public function parIssue(MonthGrid $grille, Collection $demandes): array
    {
        return array_map(fn (BookingOutcome $issue) => new SeriesData(
            cle: $issue->value,
            label: $issue->label(),
            teinte: $issue->teinte(),
            valeurs: $grille->compter(
                $demandes->filter(fn (Booking $b) => BookingOutcome::de($b->status) === $issue),
                $this->moisDeCreation(...),
            ),
        ), BookingOutcome::cases());
    }

    /** @return list<SeriesData> */
    public function reponse(MonthGrid $grille, Collection $demandes): array
    {
        return [new SeriesData('taux', 'Demandes répondues', 'encre', $grille->parMois($demandes, $this->moisDeCreation(...), $this->taux(...)))];
    }

    /** @return list<SeriesData> */
    public function delai(MonthGrid $grille, Collection $demandes): array
    {
        return [new SeriesData('mediane', 'Délai médian de réponse', 'terre', $grille->parMois($demandes, $this->moisDeCreation(...), $this->delaiMedian(...)))];
    }

    /** La part, en %, des demandes tranchées qui ont reçu une réponse ; `null` s'il n'y en a aucune. */
    public function taux(Collection $demandes): ?float
    {
        $tranchees = $demandes->filter(fn (Booking $b) => BookingOutcome::de($b->status)->tranchee());

        if ($tranchees->isEmpty()) {
            return null;
        }

        return round($tranchees->filter(fn (Booking $b) => $b->answered_at !== null)->count() / $tranchees->count() * 100);
    }

    /** En heures, à un chiffre après la virgule ; `null` si personne n'a répondu. */
    public function delaiMedian(Collection $demandes): ?float
    {
        $heures = $demandes
            ->filter(fn (Booking $b) => $b->answered_at !== null)
            ->map(fn (Booking $b) => max(0, $b->created_at->diffInMinutes($b->answered_at)) / 60);

        $mediane = Median::de($heures);

        return $mediane === null ? null : round($mediane, 1);
    }

    /** @return list<BarData> les dix destinations les plus demandées */
    public function parDestination(Collection $demandes): array
    {
        return $demandes
            ->groupBy(fn (Booking $b) => $b->listing?->destination?->name ?? 'Logement retiré')
            ->map(fn (Collection $lignes, string $nom) => new BarData($nom, $lignes->count()))
            ->sortByDesc(fn (BarData $barre) => $barre->valeur)
            ->values()->take(10)->all();
    }

    private function moisDeCreation(Booking $b): string
    {
        return $b->created_at->format('Y-m');
    }
}
