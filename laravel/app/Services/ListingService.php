<?php

namespace App\Services;

use App\Data\ListingData;
use App\Data\ListingDetailData;
use App\Data\ListingFiltreData;
use App\Exceptions\ListingNotFoundException;
use App\Repositories\Contracts\ListingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListingService
{
    public function __construct(
        private ListingRepositoryInterface $repository,
        private AvailabilityService $availability,
        private ConfirmationService $confirmations,
    ) {}

    /**
     * Le jeu complet servi à l'accueil. La grille y est petite et le rail
     * doit répondre à l'instant : le filtrage se fait côté client, sur ces
     * mêmes données. L'API, elle, passe par search().
     *
     * @return array<int, ListingData>
     */
    public function forHome(bool $includeDemo): array
    {
        return $this->repository->published($includeDemo)
            ->map(fn ($l) => ListingData::fromModel($l))
            ->values()
            ->all();
    }

    public function search(ListingFiltreData $filtre, bool $includeDemo): LengthAwarePaginator
    {
        return $this->repository->paginate($filtre, $includeDemo)
            ->through(fn ($l) => ListingData::fromModel($l));
    }

    /**
     * La fiche complète : la liste sert les trois équipements mis en avant,
     * la fiche les sert tous, groupés par rubrique.
     */
    public function show(string $slug, bool $includeDemo): ListingDetailData
    {
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
    public function similar(string $slug, bool $includeDemo, int $limit = 3): array
    {
        $listing = $this->repository->findBySlug($slug, $includeDemo)
            ?? throw new ListingNotFoundException($slug);

        return $this->repository->similar($listing, $limit, $includeDemo)
            ->map(fn ($l) => ListingData::fromModel($l))
            ->values()
            ->all();
    }

    /** @return array{kinds: array<int, string>, priceMin: int, priceMax: int, total: int} */
    public function facets(bool $includeDemo): array
    {
        return $this->repository->facets($includeDemo);
    }
}
