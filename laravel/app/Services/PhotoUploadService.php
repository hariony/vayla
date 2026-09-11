<?php

namespace App\Services;

use App\Contracts\Listings\ListingGallery;
use App\Contracts\Photos\PhotoProcessor;
use App\Contracts\Photos\PhotoStorage;
use App\Contracts\Repositories\ListingGalleryRepositoryInterface;
use App\Exceptions\PhotoRefusedException;
use App\Models\Listing;
use App\Models\Photo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * La galerie d'une annonce de propriétaire : ajouter, retirer, ranger.
 *
 * Le traitement de l'image — recadrage, paliers, compression — vit derrière
 * `PhotoProcessor` (`Services/Photos/GdPhotoProcessor`), et les fichiers
 * derrière `PhotoStorage` : un seul endroit refuse les photos floues, pour les
 * annonces comme pour les destinations.
 *
 * Ces photos vont dans `public/images/annonces/`, jamais dans `lieux/` : ce
 * dernier est réservé aux photographies de Commons, qui portent un crédit
 * obligatoire et que `PhotoFilesTest` surveille fichier par fichier.
 */
class PhotoUploadService implements ListingGallery
{
    public function __construct(
        private PhotoProcessor $traitement,
        private PhotoStorage $stockage,
        private ListingGalleryRepositoryInterface $galeries,
    ) {}

    public function ajouter(Listing $listing, UploadedFile $fichier, ?string $legende = null): Photo
    {
        $cle = $listing->id.'/'.Str::lower(Str::random(16));

        try {
            $plafond = $this->traitement->produire($fichier, 'annonces', $cle);
        } catch (RuntimeException $e) {
            throw new PhotoRefusedException($e->getMessage(), previous: $e);
        }

        // La légende est facultative, mais le champ ne l'est pas : une photo
        // sans texte alternatif n'existe pas pour un lecteur d'écran.
        $photo = $this->galeries->creerPhoto($cle, $plafond, $legende ?: $listing->title);
        $this->galeries->accrocher($listing, $photo);

        return $photo;
    }

    public function retirer(Listing $listing, Photo $photo): void
    {
        $this->galeries->decrocher($listing, $photo);

        if (! $photo->estDuProprietaire()) {
            return;
        }

        $this->stockage->effacer($photo);
        $this->galeries->supprimer($photo);
    }

    public function reordonner(Listing $listing, array $ids): void
    {
        $siennes = $this->galeries->ids($listing);

        foreach (array_values($ids) as $position => $id) {
            // Un identifiant venu d'ailleurs ne doit pas entrer dans la
            // galerie par la porte du réordonnancement.
            if (in_array((int) $id, $siennes, true)) {
                $this->galeries->positionner($listing, (int) $id, $position);
            }
        }
    }
}
