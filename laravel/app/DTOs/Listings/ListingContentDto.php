<?php

namespace App\DTOs\Listings;

/** Tout le contenu d'une annonce saisi au back-office : la fiche, les équipements, les catégories. */
final readonly class ListingContentDto
{
    /**
     * @param  array<int, AmenityChoiceDto>  $equipements
     * @param  array<int, int>  $categories
     */
    public function __construct(
        public ListingFicheDto $fiche,
        public array $equipements,
        public array $categories,
    ) {}
}
