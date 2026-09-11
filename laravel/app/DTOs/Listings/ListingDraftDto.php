<?php

namespace App\DTOs\Listings;

/** La fiche d'annonce saisie par le propriétaire, et ses équipements. */
final readonly class ListingDraftDto
{
    /** @param  list<AmenityChoiceDto>  $equipements */
    public function __construct(
        public ListingFicheDto $fiche,
        public array $equipements,
    ) {}
}
