<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\Photo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Le téléversement des photos d'une annonce.
 *
 * **Trois résolutions à l'arrivée, jamais d'agrandissement.** Chaque photo est
 * recadrée en 4/3 puis écrite en 800, 1600 et 3200 px de large — mais on
 * s'arrête à la taille réelle de l'original, et `photos.width` retient
 * laquelle. Un `srcset` qui promettrait un 3200 inexistant ferait télécharger
 * un 404, et sur une connexion malgache un aller-retour perdu se paie cher.
 *
 * **WebP, sans exception.** Les propriétaires téléversent depuis leur
 * téléphone : un JPEG de 4 Mo servi tel quel est une fiche qui ne se charge
 * pas là où on en a le plus besoin.
 *
 * **On refuse une photo trop petite plutôt que de l'étirer.** Un original de
 * 900 px affiché sur une photo de tête de 1300 est flou, et un logement flou
 * ne se réserve pas. Le message dit la taille attendue — pas « fichier
 * invalide ».
 *
 * Ces photos vont dans `public/images/annonces/`, jamais dans `lieux/` : ce
 * dernier est réservé aux photographies de Commons, qui portent un crédit
 * obligatoire et que `PhotoFilesTest` surveille fichier par fichier.
 */
class PhotoUploadService
{
    private const PALIERS = [800, 1600, 3200];

    /** En dessous, la photo de tête est floue. */
    private const LARGEUR_MINIMALE = 1200;

    private const RATIO = 4 / 3;

    private const QUALITE = 82;

    public function dossier(): string
    {
        return public_path('images/annonces');
    }

    /**
     * Ajoute une photo à l'annonce, en dernière position.
     *
     * @throws RuntimeException si l'image est illisible ou trop petite
     */
    public function ajouter(Listing $listing, UploadedFile $fichier, ?string $legende = null): Photo
    {
        [$source, $largeur, $hauteur] = $this->ouvrir($fichier);

        if ($largeur < self::LARGEUR_MINIMALE) {
            imagedestroy($source);

            throw new RuntimeException(
                "Cette photo fait {$largeur} pixels de large : il en faut au moins "
                .self::LARGEUR_MINIMALE.'. Prenez-la avec l’appareil photo du téléphone plutôt '
                .'que dans une conversation, qui les réduit.'
            );
        }

        $recadree = $this->recadrer($source, $largeur, $hauteur);
        imagedestroy($source);

        $cle = $listing->id.'/'.Str::lower(Str::random(16));
        $plafond = $this->ecrire($recadree, $cle);
        imagedestroy($recadree);

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

        foreach (self::PALIERS as $largeur) {
            $chemin = $this->dossier()."/{$photo->key}-{$largeur}.webp";

            if (is_file($chemin)) {
                unlink($chemin);
            }
        }

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

    /** @return array{0: \GdImage, 1: int, 2: int} */
    private function ouvrir(UploadedFile $fichier): array
    {
        $donnees = @file_get_contents($fichier->getRealPath());
        $image = $donnees ? @imagecreatefromstring($donnees) : false;

        if (! $image) {
            throw new RuntimeException("Ce fichier n'est pas une image que nous savons lire. JPEG, PNG ou WebP.");
        }

        // Les photos de téléphone portent une orientation EXIF : sans ce
        // redressement, une photo prise à la verticale s'affiche couchée.
        $image = $this->redresser($image, $fichier->getRealPath());

        return [$image, imagesx($image), imagesy($image)];
    }

    private function redresser(\GdImage $image, string $chemin): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($chemin);
        $angle = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $pivotee = imagerotate($image, $angle, 0);
        imagedestroy($image);

        return $pivotee ?: $image;
    }

    /** Recadrage centré en 4/3 : la même proportion que toutes les vignettes du site. */
    private function recadrer(\GdImage $source, int $largeur, int $hauteur): \GdImage
    {
        $ratio = $largeur / $hauteur;

        if ($ratio > self::RATIO) {
            $h = $hauteur;
            $w = (int) round($hauteur * self::RATIO);
        } else {
            $w = $largeur;
            $h = (int) round($largeur / self::RATIO);
        }

        $x = (int) round(($largeur - $w) / 2);
        $y = (int) round(($hauteur - $h) / 2);

        $cible = imagecreatetruecolor($w, $h);
        imagecopy($cible, $source, 0, 0, $x, $y, $w, $h);

        return $cible;
    }

    /**
     * Écrit les paliers disponibles et renvoie le plus grand réellement
     * produit. **Jamais d'agrandissement** : `photos.width` doit dire la
     * vérité sur ce que porte le disque.
     */
    private function ecrire(\GdImage $image, string $cle): int
    {
        $source = imagesx($image);
        $dossier = $this->dossier().'/'.dirname($cle);

        if (! is_dir($dossier)) {
            mkdir($dossier, 0o755, true);
        }

        $plafond = self::PALIERS[0];

        foreach (self::PALIERS as $largeur) {
            if ($largeur > $source && $largeur !== self::PALIERS[0]) {
                break;
            }

            $w = min($largeur, $source);
            $h = (int) round($w / self::RATIO);

            $redimensionnee = imagecreatetruecolor($w, $h);
            imagecopyresampled($redimensionnee, $image, 0, 0, 0, 0, $w, $h, $source, imagesy($image));
            imagewebp($redimensionnee, $this->dossier()."/{$cle}-{$largeur}.webp", self::QUALITE);
            imagedestroy($redimensionnee);

            $plafond = $largeur;
        }

        return $plafond;
    }
}
