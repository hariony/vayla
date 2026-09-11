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

    /** @return array<int, array{id: int, highlight: bool}> la forme qu'attend encore `OwnerListingService` (lot 3) */
    public function choixEquipements(): array
    {
        return array_map(fn (AmenityChoiceDto $c) => ['id' => $c->id, 'highlight' => $c->highlight], $this->equipements);
    }
}
