<?php

namespace App\Data;

use App\Enums\ListingSort;
use Spatie\LaravelData\Data;

/**
 * Critères de recherche d'annonces.
 *
 * L'accueil filtre côté client — la grille est petite et le rail doit
 * répondre à l'instant. Ce filtre sert l'API mobile et, plus tard, la
 * pagination : les deux chemins traversent le même service, c'est
 * précisément ce que l'architecture achète.
 */
class ListingFiltreData extends Data
{
    /**
     * `amenities` est une conjonction, pas une disjonction : demander
     * « groupe électrogène + piscine privée » ne doit pas ramener les
     * logements qui n'ont que l'un des deux. C'est le seul comportement
     * défendable — un voyageur qui coche deux cases pose deux conditions.
     *
     * @param  array<int, string>  $amenities
     */
    public function __construct(
        public readonly ?string $destination = null,
        public readonly ?string $category = null,
        public readonly ?int $guests = null,
        /** Les nuits demandées, ou `null` si la recherche ne porte pas de dates. */
        public readonly ?SejourData $sejour = null,
        public readonly ?int $minTrust = null,
        public readonly ?string $kind = null,
        public readonly ?int $maxPrice = null,
        public readonly array $amenities = [],
        public readonly ListingSort $sort = ListingSort::Confiance,
        public readonly int $page = 1,
        public readonly int $perPage = 24,
    ) {}
}
