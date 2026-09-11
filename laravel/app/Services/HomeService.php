<?php

namespace App\Services;

use App\Data\Pages\HomePageData;
use App\Services\Support\DemoMode;

/**
 * Orchestrateur de la page d'accueil : il assemble ce que les cinq services
 * métier produisent. Le drapeau `vayla.demo` lui vient de `DemoMode`, seul à
 * le lire.
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
        private DemoMode $demo,
    ) {}

    public function page(): HomePageData
    {
        return new HomePageData(
            destinations: $this->destinations->atlas(),
            trustLevels: $this->trust->ladder(),
            categories: $this->categories->rail(),
            listings: $this->listings->forHome(),
            demo: $this->demo->actif(),
            photos: $this->photos->map(),
            credits: $this->photos->credits(),
        );
    }
}
