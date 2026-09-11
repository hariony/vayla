<?php

namespace App\Contracts\Repositories;

use App\Models\Listing;
use App\Models\Photo;

/**
 * La galerie d'une annonce. **La position 0 est la couverture**, et il n'y a
 * pas d'autre moyen de la désigner.
 */
interface ListingGalleryRepositoryInterface
{
    /** Une photo du propriétaire, dans `annonces` : sans auteur ni licence à citer. */
    public function creerPhoto(string $cle, int $largeur, string $legende): Photo;

    /** Au bout de la galerie. */
    public function accrocher(Listing $listing, Photo $photo): void;

    public function decrocher(Listing $listing, Photo $photo): void;

    public function positionner(Listing $listing, int $photoId, int $position): void;

    /** @return list<int> les photos de la galerie */
    public function ids(Listing $listing): array;

    /** Une photo de **cette** galerie, ou `null` : l'identifiant vient du navigateur. */
    public function photo(Listing $listing, int $photoId): ?Photo;

    public function supprimer(Photo $photo): void;
}
