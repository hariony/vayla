<?php

namespace App\Services;

use App\Contracts\Listings\ListingGallery;
use App\Contracts\Photos\PhotoProcessor;
use App\Contracts\Photos\PhotoStorage;
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
    ) {}

    /**
     * Ajoute une photo à l'annonce, en dernière position.
     *
     * @throws RuntimeException si l'image est illisible ou trop petite
     */
    public function ajouter(Listing $listing, UploadedFile $fichier, ?string $legende = null): Photo
    {
        $cle = $listing->id.'/'.Str::lower(Str::random(16));
        $plafond = $this->traitement->produire($fichier, 'annonces', $cle);

        $photo = Photo::create([
            'key' => $cle,
            'folder' => 'annonces',
            'width' => $plafond,
            'is_ai' => false,
            // La légende est facultative, mais le champ ne l'est pas : une
            // photo sans texte alternatif n'existe pas pour un lecteur d'écran.
            'caption' => $legende ?: $listing->title,
        ]);

        $listing->photos()->attach($photo->id, [
            'position' => (int) $listing->photos()->max('position') + 1,
        ]);

        return $photo;
    }

    /**
     * Retire une photo de l'annonce **et du disque**.
     *
     * Une photo de propriétaire n'appartient qu'à son annonce — contrairement
     * à celles de Commons, qu'une destination et une annonce peuvent partager.
     * La laisser sur le disque après suppression accumulerait des fichiers que
     * plus rien ne nomme.
     */
    public function retirer(Listing $listing, Photo $photo): void
    {
        $listing->photos()->detach($photo->id);

        if (! $photo->estDuProprietaire()) {
            return;
        }

        $this->stockage->effacer($photo);
        $photo->delete();
    }

    /**
     * Réordonne la galerie. **La position 0 est la couverture** — il n'y a pas
     * de colonne `photo_id` à côté, qui aurait permis qu'une couverture
     * n'appartienne pas à la galerie.
     *
     * @param  array<int, int>  $ids  dans l'ordre voulu
     */
    public function reordonner(Listing $listing, array $ids): void
    {
        $siennes = $listing->photos->pluck('id')->all();

        foreach (array_values($ids) as $position => $id) {
            // Un identifiant venu d'ailleurs ne doit pas entrer dans la
            // galerie par la porte du réordonnancement.
            if (in_array((int) $id, $siennes, true)) {
                $listing->photos()->updateExistingPivot((int) $id, ['position' => $position]);
            }
        }
    }
}
