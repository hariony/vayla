<?php

namespace App\Services;

use App\Data\ConfirmationData;
use App\Data\ConfirmationSummaryData;
use App\Enums\ConfirmationPoint;
use App\Models\Listing;
use App\Models\StayConfirmation;
use Illuminate\Support\Collection;

/**
 * Les séjours confirmés d'un logement, et leur récapitulatif.
 *
 * Deux règles qui ne se négocient pas :
 *
 * 1. **Aucune moyenne n'est calculée.** Le service ne produit ni note, ni
 *    score, ni pourcentage global. Un chiffre unique finit toujours par
 *    remplacer les faits qu'il résume, et redevient la note sur cinq que
 *    tout le produit refuse.
 * 2. **Un point signalé sort avec le point confirmé.** `flagged` est compté
 *    et publié sur la même ligne que `confirmed`. Ne remonter que les « oui »
 *    donnerait une fiche complaisante — exactement ce que Vayla reproche aux
 *    plateformes qui enterrent les mauvais avis en page trois.
 */
class ConfirmationService
{
    /** @return Collection<int, StayConfirmation> */
    private function rows(Listing $listing, bool $includeDemo): Collection
    {
        return $listing->confirmations
            ->when(! $includeDemo, fn (Collection $c) => $c->where('is_demo', false))
            ->values();
    }

    /** @return array<int, ConfirmationData> */
    public function all(Listing $listing, bool $includeDemo): array
    {
        return $this->rows($listing, $includeDemo)
            ->map(fn ($c) => ConfirmationData::fromModel($c))
            ->all();
    }

    public function summary(Listing $listing, bool $includeDemo): ConfirmationSummaryData
    {
        $rows = $this->rows($listing, $includeDemo);

        $points = collect(ConfirmationPoint::ordered())
            ->map(function (ConfirmationPoint $p) use ($rows) {
                $confirmed = $rows->filter(fn ($c) => in_array($p->value, $c->points ?? [], true))->count();
                $flagged = $rows->filter(fn ($c) => in_array($p->value, $c->flagged ?? [], true))->count();

                return [
                    'key' => $p->value,
                    'label' => $p->short(),
                    'long' => $p->label(),
                    'icon' => $p->icon(),
                    'confirmed' => $confirmed,
                    'flagged' => $flagged,
                    // Le total des réponses reçues sur CE point : un voyageur
                    // qui n'a pas répondu ne compte ni pour ni contre.
                    'answered' => $confirmed + $flagged,
                ];
            })
            // Un point sur lequel personne ne s'est prononcé n'a pas de barre :
            // une barre vide se lit comme un échec.
            ->filter(fn (array $p) => $p['answered'] > 0)
            ->values()
            ->all();

        return new ConfirmationSummaryData(
            stays: $rows->count(),
            nights: (int) $rows->sum('nights'),
            points: $points,
            demo: $rows->isNotEmpty() && $rows->every(fn ($c) => $c->is_demo),
        );
    }
}
