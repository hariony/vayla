<?php

namespace App\Services;

/**
 * Orchestrateur de la page d'accueil : il assemble ce que les cinq services
 * métier produisent, et il est le seul à connaître le drapeau `vayla.demo`.
 *
 * Ce drapeau tient deux choses ensemble : les annonces fictives servies, et
 * le bandeau « Aperçu » qui le dit à l'écran. Les séparer rendrait possible
 * d'afficher des annonces fictives sans le signaler — exactement ce que
 * Vayla reproche aux annonces volées.
 */
class HomeService
{
    public function __construct(
        private DestinationService $destinations,
        private ListingService $listings,
        private CategoryService $categories,
        private TrustLadderService $trust,
        private PhotoService $photos,
    ) {}

    /** @return array<string, mixed> */
    public function props(): array
    {
        $demo = (bool) config('vayla.demo');

        return [
            'destinations' => $this->destinations->atlas($demo),
            'trustLevels' => $this->trust->ladder(),
            'categories' => $this->categories->rail(),
            'listings' => $this->listings->forHome($demo),
            'demo' => $demo,
            'photos' => $this->photos->map(),
            'credits' => $this->photos->credits(),
        ];
    }
}
