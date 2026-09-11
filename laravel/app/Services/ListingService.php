<?php

namespace App\Services;

use App\Contracts\Repositories\ListingRepositoryInterface;
use App\Data\Api\ListingIndexData;
use App\Data\Api\ListingIndexMetaData;
use App\Data\ListingData;
use App\Data\ListingDetailData;
use App\Data\ListingFiltreData;
use App\DTOs\Listings\ListingFacetsDto;
use App\Exceptions\ListingNotFoundException;
use App\Services\Support\DemoMode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListingService
{
    public function __construct(
        private ListingRepositoryInterface $repository,
        private AvailabilityService $availability,
        private ConfirmationService $confirmations,
        private DemoMode $demo,
    ) {}

    /**
     * Le jeu complet servi à l'accueil. La grille y est petite et le rail
     * doit répondre à l'instant : le filtrage se fait côté client, sur ces
     * mêmes données. L'API, elle, passe par search().
     *
     * @return array<int, ListingData>
     */
    public function forHome(): array
    {
        return $this->repository->published($this->demo->actif())
            ->map(fn ($l) => ListingData::fromModel($l))
            ->values()
            ->all();
    }

    public function search(ListingFiltreData $filtre): LengthAwarePaginator
    {
        return $this->repository->paginate($filtre, $this->demo->actif())
            ->through(fn ($l) => ListingData::fromModel($l));
    }

    /**
     * La fiche complète : la liste sert les trois équipements mis en avant,
     * la fiche les sert tous, groupés par rubrique.
     */
    public function show(string $slug): ListingDetailData
    {
        $includeDemo = $this->demo->actif();
        $listing = $this->repository->findBySlug($slug, $includeDemo)
            ?? throw new ListingNotFoundException($slug);

        return ListingDetailData::fromModel(
            $listing,
            $this->availability->calendar($listing),
            $this->confirmations->all($listing, $includeDemo),
            $this->confirmations->summary($listing, $includeDemo),
        );
    }

    /**
     * Les autres logements de la même destination. Trois : au-delà, le bloc
     * concurrence la fiche au lieu de la prolonger.
     *
     * @return array<int, ListingData>
     */
    public function similar(string $slug, int $limit = 3): array
    {
        $includeDemo = $this->demo->actif();
        $listing = $this->repository->findBySlug($slug, $includeDemo)
            ?? throw new ListingNotFoundException($slug);

        return $this->repository->similar($listing, $limit, $includeDemo)
            ->map(fn ($l) => ListingData::fromModel($l))
            ->values()
            ->all();
    }

    /** @return array{kinds: array<int, string>, priceMin: int, priceMax: int, total: int} */
    public function facets(): ListingFacetsDto
    {
        return $this->repository->facets($this->demo->actif());
    }

    /**
     * Une page de `/api/v1/listings` : les annonces, et où l'on en est. `demo`
     * y est dit, comme le bandeau « Aperçu » le dit à l'écran.
     */
    public function apiPage(ListingFiltreData $filtre): ListingIndexData
    {
        $page = $this->search($filtre);

        return new ListingIndexData($page->items(), ListingIndexMetaData::fromPaginator($page, $this->demo->actif()));
    }
}
