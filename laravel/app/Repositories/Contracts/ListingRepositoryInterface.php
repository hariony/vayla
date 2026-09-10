<?php

namespace App\Repositories\Contracts;

use App\Data\ListingFiltreData;
use App\Models\Listing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ListingRepositoryInterface
{
    /** @return Collection<int, Listing> */
    public function published(bool $includeDemo): Collection;

    public function paginate(ListingFiltreData $filtre, bool $includeDemo): LengthAwarePaginator;

    /**
     * `$includeDemo` est le même drapeau que partout ailleurs : sans lui, une
     * URL directe servirait encore une annonce fictive alors que la grille
     * s'est vidée.
     */
    public function findBySlug(string $slug, bool $includeDemo): ?Listing;

    /** Les autres logements de la même destination. @return Collection<int, Listing> */
    public function similar(Listing $listing, int $limit, bool $includeDemo): Collection;

    /**
     * Les annonces d'une destination, dans l'ordre du catalogue.
     *
     * @return Collection<int, Listing>
     */
    public function forDestination(string $slug, bool $includeDemo): Collection;

    /**
     * De quoi construire le panneau de filtres sans inventer de valeur :
     * les types réellement présents et les bornes de prix réelles.
     *
     * @return array{kinds: array<int, string>, priceMin: int, priceMax: int, total: int}
     */
    public function facets(bool $includeDemo): array;
}
