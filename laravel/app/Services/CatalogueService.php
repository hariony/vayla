<?php

namespace App\Services;

use App\Data\ListingFiltreData;
use App\Data\SejourData;
use App\Enums\ListingSort;
use App\Enums\PropertyType;

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
    ) {}

    /** @return array<string, mixed> */
    public function index(ListingFiltreData $filtre): array
    {
        $demo = (bool) config('vayla.demo');
        $page = $this->listings->search($filtre, $demo);
        $facets = $this->listings->facets($demo);

        return [
            'listings' => $page->items(),
            'meta' => [
                'page' => $page->currentPage(),
                'perPage' => $page->perPage(),
                'total' => $page->total(),
                'pages' => $page->lastPage(),
            ],
            'filtre' => $this->filtreArray($filtre),
            'facets' => [
                'kinds' => $this->kinds($facets['kinds']),
                'priceMin' => $facets['priceMin'],
                'priceMax' => $facets['priceMax'],
                'catalogue' => $facets['total'],
            ],
            'amenityFilters' => $this->amenities->filters(),
            'sorts' => $this->sorts(),
            'destinations' => $this->destinations->atlas($demo),
            'categories' => $this->categories->rail(),
            'trustLevels' => $this->trust->ladder(),
            'demo' => $demo,
            'photos' => $this->photos->map(),
            'credits' => $this->photos->credits(),
        ];
    }

    /**
     * @param  SejourData|null  $sejour  le séjour à reporter dans le calendrier
     * @return array<string, mixed>
     */
    public function show(string $slug, ?SejourData $sejour = null): array
    {
        $demo = (bool) config('vayla.demo');

        return [
            'fiche' => $this->listings->show($slug, $demo),
            'sejour' => $sejour,
            'similar' => $this->listings->similar($slug, $demo),
            'trustLevels' => $this->trust->ladder(),
            'demo' => $demo,
            'photos' => $this->photos->map(),
            'credits' => $this->photos->credits(),
        ];
    }

    /**
     * Les critères actifs, renvoyés au front tels qu'il les a envoyés : la
     * page se recharge par visite Inertia, l'état des champs doit survivre
     * au retour arrière du navigateur comme au partage d'un lien.
     *
     * @return array<string, mixed>
     */
    private function filtreArray(ListingFiltreData $filtre): array
    {
        return [
            'destination' => $filtre->destination,
            'category' => $filtre->category,
            'guests' => $filtre->guests,
            'arrival' => $filtre->sejour?->arrival,
            'departure' => $filtre->sejour?->departure,
            'nights' => $filtre->sejour?->nights,
            'min_trust' => $filtre->minTrust,
            'kind' => $filtre->kind,
            'max_price' => $filtre->maxPrice,
            'amenities' => $filtre->amenities,
            'sort' => $filtre->sort->value,
        ];
    }

    /**
     * @param  array<int, string>  $present
     * @return array<int, array{key: string, label: string}>
     */
    private function kinds(array $present): array
    {
        return array_values(array_map(
            static fn (PropertyType $t) => ['key' => $t->value, 'label' => $t->label()],
            array_filter(
                PropertyType::ordered(),
                static fn (PropertyType $t) => in_array($t->value, $present, true)
            )
        ));
    }

    /** @return array<int, array{key: string, label: string}> */
    private function sorts(): array
    {
        return array_map(
            static fn (ListingSort $s) => ['key' => $s->value, 'label' => $s->label()],
            ListingSort::ordered()
        );
    }
}
