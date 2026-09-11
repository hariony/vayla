<?php

namespace App\DTOs\Listings;

/**
 * Ce que le dépôt sait des bornes du catalogue, en une requête d'agrégat : les
 * types de logement présents (clés brutes), la fourchette de prix, le total.
 */
final readonly class ListingFacetsDto
{
    /** @param  list<string>  $kinds */
    public function __construct(
        public array $kinds,
        public int $priceMin,
        public int $priceMax,
        public int $total,
    ) {}
}
