<?php

namespace App\Services\Photos;

use App\Contracts\Photos\PhotoProcessor;
use App\Contracts\Photos\PhotoStorage;
use App\Services\Images\ImageSource;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Le traitement des photos reçues, en GD — aucune dépendance ajoutée.
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
 */
final class GdPhotoProcessor implements PhotoProcessor
{
    /** En dessous, la photo de tête est floue. */
    private const LARGEUR_MINIMALE = 1200;

    private const RATIO = 4 / 3;

    /** @var array<int, int> la qualité WebP de chaque palier */
    private const QUALITES = [800 => 82, 1600 => 79, 3200 => 74];

    public function __construct(private PhotoStorage $stockage) {}

    public function produire(UploadedFile $fichier, string $dossier, string $cle): int
    {
        $source = ImageSource::ouvrir($fichier->getRealPath());
        $this->exigerLargeur($source->largeur);

        [$l, $h] = $this->recadrage($source->largeur, $source->hauteur);
        $paliers = $this->paliersPossibles($l);
        $plafond = end($paliers);

        $image = $source->extraire(
            (int) round(($source->largeur - $l) / 2), (int) round(($source->hauteur - $h) / 2), $l, $h,
            $plafond, (int) round($plafond / self::RATIO),
        );

        $this->ecrire($image, array_reverse($paliers), $cle, $dossier);

        return $plafond;
    }

    private function exigerLargeur(int $largeur): void
    {
        if ($largeur < self::LARGEUR_MINIMALE) {
            throw new RuntimeException(
                "Cette photo fait {$largeur} pixels de large : il en faut au moins "
                .self::LARGEUR_MINIMALE.'. Prenez-la avec l’appareil photo du téléphone plutôt '
                .'que dans une conversation, qui les réduit.'
            );
        }
    }

    /**
     * Le recadrage 4/3, centré : la même proportion que toutes les vignettes du site.
     *
     * @return array{0: int, 1: int}
     */
    private function recadrage(int $largeur, int $hauteur): array
    {
        return $largeur / $hauteur > self::RATIO
            ? [(int) round($hauteur * self::RATIO), $hauteur]
            : [$largeur, (int) round($largeur / self::RATIO)];
    }

    /**
     * Les paliers que l'original permet — **jamais d'agrandissement** :
     * `photos.width` doit dire la vérité sur ce que porte le disque.
     *
     * @return array<int, int> croissants
     */
    private function paliersPossibles(int $largeurRecadree): array
    {
        return array_values(array_filter(self::PALIERS, fn (int $p) => $p === self::PALIERS[0] || $p <= $largeurRecadree));
    }

    /**
     * Écrit chaque palier, du plus grand au plus petit, chacun réduit depuis
     * le précédent.
     *
     * @param  array<int, int>  $paliers  décroissants
     */
    private function ecrire(\GdImage $image, array $paliers, string $cle, string $sousDossier): void
    {
        $racine = $this->stockage->dossier($sousDossier);
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
