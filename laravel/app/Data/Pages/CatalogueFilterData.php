<?php

namespace App\Data\Pages;

use App\Data\ListingFiltreData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

/**
 * Les critères en cours, tels que le panneau de filtres les relit. Les noms
 * sont ceux de l'adresse (`min_trust`, `max_price`) : c'est elle qui porte la
 * recherche — un filtre se partage et survit au retour arrière.
 */
final class CatalogueFilterData extends Data
{
    /** @param  list<string>  $amenities */
    public function __construct(
        public readonly ?string $destination,
        public readonly ?string $category,
        public readonly ?int $guests,
        public readonly ?string $arrival,
        public readonly ?string $departure,
        public readonly ?int $nights,
        #[MapOutputName('min_trust')]
        public readonly ?int $minTrust,
        public readonly ?string $kind,
        #[MapOutputName('max_price')]
        public readonly ?int $maxPrice,
        public readonly array $amenities,
        public readonly string $sort,
    ) {}

    public static function depuis(ListingFiltreData $f): self
    {
        return new self(
            destination: $f->destination,
            category: $f->category,
            guests: $f->guests,
            arrival: $f->sejour?->arrival,
            departure: $f->sejour?->departure,
            nights: $f->sejour?->nights,
            minTrust: $f->minTrust,
            kind: $f->kind,
            maxPrice: $f->maxPrice,
            amenities: $f->amenities,
            sort: $f->sort->value,
        );
    }
}
