<?php

namespace App\Contracts\Listings;

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
    /** @param  array<string, mixed>  $donnees */
    public function creer(Owner $owner, array $donnees): Listing;

    /** @param  array<int, array{id: int, highlight?: bool}>  $choix */
    public function poserEquipements(Listing $listing, array $choix): void;

    /** @return array<string, mixed> */
    public function pourEdition(Listing $listing): array;

    /** @return array<string, mixed> */
    public function vocabulaire(): array;
}
