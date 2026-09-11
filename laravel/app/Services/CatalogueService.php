<?php

namespace App\Services;

use App\Data\KeyLabelData;
use App\Data\ListingFiltreData;
use App\Data\Pages\CatalogueFacetsData;
use App\Data\Pages\CatalogueFilterData;
use App\Data\Pages\CatalogueMetaData;
use App\Data\Pages\CataloguePageData;
use App\Data\Pages\ListingPageData;
use App\Data\SejourData;
use App\DTOs\Listings\ListingFacetsDto;
use App\Enums\ListingSort;
use App\Enums\PropertyType;
use App\Services\Support\DemoMode;

/**
 * Orchestrateur des pages « logements » : la grille filtrable et la fiche.
 *
 * Même rôle que HomeService, autre écran — et surtout, même drapeau
 * `vayla.demo`. Il ne vaut pas que pour la grille : sans lui sur la fiche,
 * une URL directe servirait encore une annonce fictive alors que le
 * catalogue s'est vidé, et le bandeau « Aperçu » ne serait plus solidaire
 * de ce qui est réellement montré.
 *
 * Les facettes du panneau de filtres ne sont jamais inventées : les types
 * proposés sont ceux réellement présents en base, les bornes de prix sont
 * les vraies. Proposer « Lodge » sans aucun lodge produirait un filtre qui
 * ne ramène rien, et un catalogue qui a l'air cassé.
 */
class CatalogueService
{
    public function __construct(
        private ListingService $listings,
        private DestinationService $destinations,
        private CategoryService $categories,
        private AmenityService $amenities,
        private TrustLadderService $trust,
        private PhotoService $photos,
        private DemoMode $demo,
    ) {}

    public function index(ListingFiltreData $filtre): CataloguePageData
    {
        $page = $this->listings->search($filtre);

        return new CataloguePageData(
            listings: $page->items(),
            meta: CatalogueMetaData::fromPaginator($page),
            filtre: CatalogueFilterData::depuis($filtre),
            facets: $this->facets($this->listings->facets()),
            amenityFilters: $this->amenities->filters(),
            sorts: array_map(static fn (ListingSort $s) => new KeyLabelData($s->value, $s->label()), ListingSort::ordered()),
            destinations: $this->destinations->atlas(),
            categories: $this->categories->rail(),
            trustLevels: $this->trust->ladder(),
            demo: $this->demo->actif(),
            photos: $this->photos->map(),
            credits: $this->photos->credits(),
        );
    }

    public function show(string $slug, ?SejourData $sejour = null): ListingPageData
    {
        return new ListingPageData(
            fiche: $this->listings->show($slug),
            sejour: $sejour,
            similar: $this->listings->similar($slug),
            trustLevels: $this->trust->ladder(),
            demo: $this->demo->actif(),
            photos: $this->photos->map(),
            credits: $this->photos->credits(),
        );
    }

    /** Seuls les types réellement présents, dans l'ordre de l'enum. */
    private function facets(ListingFacetsDto $facets): CatalogueFacetsData
    {
        $kinds = array_filter(PropertyType::ordered(), static fn (PropertyType $t) => in_array($t->value, $facets->kinds, true));

        return new CatalogueFacetsData(
            kinds: array_values(array_map(static fn (PropertyType $t) => new KeyLabelData($t->value, $t->label()), $kinds)),
            priceMin: $facets->priceMin,
            priceMax: $facets->priceMax,
            catalogue: $facets->total,
        );
    }
}
