<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\Photo;
use App\Services\Images\ImageSource;
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
 * **WebP, sans exception, et une qualité par palier.** Les propriétaires
 * téléversent depuis leur téléphone : un JPEG de 4 Mo servi tel quel est une
 * fiche qui ne se charge pas là où on en a le plus besoin. La qualité baisse
 * quand la taille monte — 82 en 800, 79 en 1600, 74 en 3200 — parce que le
 * 3200 n'est demandé que par les écrans à haute densité, où chaque pixel de
 * l'image fait un demi-pixel à l'écran et où la compression se voit deux fois
 * moins. Mesuré sur trois photographies de Commons (dont une de 48 Mpx) : un
 * cinquième de disque en moins, sans différence visible sur les détails.
 *
 * **Chaque palier naît du précédent** (3200 → 1600 → 800), pas de
 * l'original : réduire par moitié garde le détail mieux qu'un grand saut, et
 * l'original n'est décodé qu'une fois (`ImageSource`, qui le lit sans jamais
 * le recopier en pleine taille — une photo de 48 Mpx faisait tomber l'envoi
 * sur la limite de mémoire de PHP).
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

    /**
     * Le poids maximal d'un fichier reçu, en kilo-octets : **40 Mo**, de quoi
     * accepter l'original d'un appareil de 48 Mpx. Le navigateur réduit
     * d'ordinaire la photo avant l'envoi (`Support/preparerPhoto.js`) ; cette
     * borne sert quand il n'a pas pu. PHP en accepte 50 (`docker/php/php.ini`),
     * nginx 100.
     */
    public const POIDS_MAX_KO = 40960;

    /** En dessous, la photo de tête est floue. */
    private const LARGEUR_MINIMALE = 1200;

    private const RATIO = 4 / 3;

    /** @var array<int, int> la qualité WebP de chaque palier */
    private const QUALITES = [800 => 82, 1600 => 79, 3200 => 74];

    public function dossier(string $dossier = 'annonces'): string
    {
        return public_path("images/{$dossier}");
    }

    /**
     * Ouvre, redresse, vérifie, recadre et écrit une photo dans
     * `public/images/{dossier}/`, et renvoie la plus grande largeur réellement
     * produite. **Le même traitement pour les annonces et les destinations** :
     * deux copies auraient fini par ne pas refuser les mêmes photos floues.
     *
     * @throws RuntimeException si l'image est illisible ou trop petite
     */
    public function produire(UploadedFile $fichier, string $dossier, string $cle): int
    {
        $source = ImageSource::ouvrir($fichier->getRealPath());
        $largeur = $source->largeur;
        $hauteur = $source->hauteur;

        if ($largeur < self::LARGEUR_MINIMALE) {
            throw new RuntimeException(
                "Cette photo fait {$largeur} pixels de large : il en faut au moins "
                .self::LARGEUR_MINIMALE.'. Prenez-la avec l’appareil photo du téléphone plutôt '
                .'que dans une conversation, qui les réduit.'
            );
        }

        // Le recadrage 4/3, centré : la même proportion que toutes les
        // vignettes du site.
        if ($largeur / $hauteur > self::RATIO) {
            $h = $hauteur;
            $w = (int) round($hauteur * self::RATIO);
        } else {
            $w = $largeur;
            $h = (int) round($largeur / self::RATIO);
        }

        // Les paliers que l'original permet — **jamais d'agrandissement** :
        // `photos.width` doit dire la vérité sur ce que porte le disque.
        $paliers = array_values(array_filter(self::PALIERS, fn (int $p) => $p === self::PALIERS[0] || $p <= $w));
        $plafond = end($paliers);

        $image = $source->extraire(
            (int) round(($largeur - $w) / 2), (int) round(($hauteur - $h) / 2), $w, $h,
            $plafond, (int) round($plafond / self::RATIO),
        );

        $this->ecrire($image, array_reverse($paliers), $cle, $dossier);

        return $plafond;
    }

    /** Efface les fichiers d'une photo, tous paliers, dans son dossier. */
    public function effacerFichiers(Photo $photo): void
    {
        foreach (self::PALIERS as $largeur) {
            $chemin = $this->dossier($photo->folder)."/{$photo->key}-{$largeur}.webp";

            if (is_file($chemin)) {
                unlink($chemin);
            }
        }
    }

    /**
     * Ajoute une photo à l'annonce, en dernière position.
     *
     * @throws RuntimeException si l'image est illisible ou trop petite
     */
    public function ajouter(Listing $listing, UploadedFile $fichier, ?string $legende = null): Photo
    {
        $cle = $listing->id.'/'.Str::lower(Str::random(16));
        $plafond = $this->produire($fichier, 'annonces', $cle);

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

        $this->effacerFichiers($photo);
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

    /**
     * Écrit chaque palier, du plus grand au plus petit, chacun réduit depuis
     * le précédent.
     *
     * @param  array<int, int>  $paliers  décroissants
     */
    private function ecrire(\GdImage $image, array $paliers, string $cle, string $sousDossier): void
    {
        $racine = $this->dossier($sousDossier);
        $dossier = $racine.'/'.dirname($cle);

        if (! is_dir($dossier)) {
            mkdir($dossier, 0o755, true);
        }

        foreach ($paliers as $largeur) {
            if (imagesx($image) !== $largeur) {
                $reduite = imagecreatetruecolor($largeur, (int) round($largeur / self::RATIO));
                imagecopyresampled($reduite, $image, 0, 0, 0, 0, imagesx($reduite), imagesy($reduite), imagesx($image), imagesy($image));
                $image = $reduite;
            }

            imagewebp($image, "{$racine}/{$cle}-{$largeur}.webp", self::QUALITES[$largeur]);
        }
    }
}
