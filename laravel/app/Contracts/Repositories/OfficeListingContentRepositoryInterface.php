<?php

namespace App\Contracts\Repositories;

use App\DTOs\Listings\ListingFicheDto;
use App\Models\Listing;
use App\Models\Photo;

/** Le contenu d'une annonce, corrigé par l'équipe. */
interface OfficeListingContentRepositoryInterface
{
    /** @return array<int, string> les colonnes réellement modifiées */
    public function modifierFiche(Listing $listing, ListingFicheDto $fiche): array;

    /** Les équipements cochés et leur mise en avant, en une chaîne comparable. */
    public function empreinteEquipements(Listing $listing): string;

    /**
     * @param  array<int, int>  $ids  des catégories **éditoriales** seulement
     * @return bool vrai si quelque chose a changé
     */
    public function poserCategories(Listing $listing, array $ids): bool;

    /** @return array<int, int> */
    public function categories(Listing $listing): array;

    /** Une photo de **cette** galerie — l'identifiant vient du navigateur. */
    public function photoDeLaGalerie(Listing $listing, int $photoId): ?Photo;

    /** Recharge ce que l'écran d'édition et le rangement lisent : équipements, photos, catégories, propriétaire. */
    public function charger(Listing $listing): Listing;
}
