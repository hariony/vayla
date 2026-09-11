<?php

namespace App\Contracts\Listings;

use App\Data\Listings\ListingFormData;
use App\Data\Listings\ListingVocabularyData;
use App\DTOs\Listings\AmenityChoiceDto;
use App\DTOs\Listings\ListingFicheDto;
use App\Models\Listing;
use App\Models\Owner;

/**
 * Rédiger une annonce : la créer, poser ses équipements, la relire pour
 * l'édition. Implémenté par `OwnerListingService`, **partagé avec l'espace
 * propriétaire** : une annonce saisie au back-office naît exactement comme
 * celle d'un propriétaire. Les tableaux de ces signatures sont la frontière du
 * lot 3, où ce service passera aux DTO.
 */
interface ListingDrafting
{
    public function creer(Owner $owner, ListingFicheDto $fiche): Listing;

    /** @param  list<AmenityChoiceDto>  $choix */
    public function poserEquipements(Listing $listing, array $choix): void;

    public function pourEdition(Listing $listing): ListingFormData;

    public function vocabulaire(): ListingVocabularyData;
}
