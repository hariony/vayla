<?php

namespace App\Services\Images;

use GdImage;
use RuntimeException;

/**
 * Une image téléversée, lue **sans être recopiée en pleine taille**.
 *
 * Une photo de téléphone récent fait 24 à 48 mégapixels : décodée par GD, elle
 * occupe 4 octets par pixel, soit jusqu'à 200 Mo. Le traitement d'avant la
 * décodait, la faisait pivoter (une seconde copie), puis la recadrait (une
 * troisième) : 534 Mo mesurés sur une photo de 48 Mpx, bien au-delà des
 * 256 Mo de PHP — l'envoi tombait sur une erreur 500 que personne ne
 * comprenait. Trois règles le réparent :
 *
 * - **les dimensions se lisent avant le décodage** (`getimagesize` ne lit que
 *   l'en-tête) : une photo trop petite ou démesurée est refusée avec sa
 *   taille, sans avoir coûté un octet ;
 * - **on recadre et on réduit en une seule passe**, directement depuis
 *   l'original vers la taille du plus grand palier : l'image pleine n'est
 *   jamais recopiée ;
 * - **l'orientation EXIF s'applique à la fin, sur la petite image** : le
 *   rectangle voulu dans l'image redressée est ramené dans l'original, puis
 *   seul le résultat pivote.
 *
 * La mémoire nécessaire est calculée d'après les dimensions et accordée pour
 * ce seul traitement.
 */
final class ImageSource
{
    /** Au-delà, même une image seule ne tient plus raisonnablement en mémoire. */
    public const MEGAPIXELS_MAX = 80;

    private const TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

    /** Largeur et hauteur de l'image **redressée**, telle qu'on la regarde. */
    public readonly int $largeur;

    public readonly int $hauteur;

    private function __construct(
        private readonly string $chemin,
        private readonly int $type,
        private readonly int $brutL,
        private readonly int $brutH,
        private readonly int $angle,
    ) {
        [$this->largeur, $this->hauteur] = abs($angle) === 90 ? [$brutH, $brutL] : [$brutL, $brutH];
    }

    /** @throws RuntimeException si le fichier n'est pas une image lisible, ou s'il est démesuré */
    public static function ouvrir(string $chemin): self
    {
        $infos = @getimagesize($chemin);

        if (! $infos || ! in_array($infos[2], self::TYPES, true) || $infos[0] < 1 || $infos[1] < 1) {
            throw new RuntimeException("Ce fichier n'est pas une image que nous savons lire. JPEG, PNG ou WebP.");
        }

        $megapixels = $infos[0] * $infos[1] / 1_000_000;

        if ($megapixels > self::MEGAPIXELS_MAX) {
            throw new RuntimeException(sprintf(
                'Cette photo fait %d mégapixels (%d × %d) : c’est plus que nous savons traiter (%d). '
                .'Exportez-la en 6 000 pixels de large au plus — la qualité affichée sur le site sera la même.',
                (int) round($megapixels), $infos[0], $infos[1], self::MEGAPIXELS_MAX,
            ));
        }

        return new self($chemin, $infos[2], $infos[0], $infos[1], self::angle($chemin, $infos[2]));
    }

    /**
     * Extrait le rectangle (`x`, `y`, `l`, `h`) **de l'image redressée** et
     * le met directement à `versL` × `versH`.
     */
    public function extraire(int $x, int $y, int $l, int $h, int $versL, int $versH): GdImage
    {
        $this->accorderMemoire();

        $source = match ($this->type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($this->chemin),
            IMAGETYPE_PNG => @imagecreatefrompng($this->chemin),
            IMAGETYPE_WEBP => @imagecreatefromwebp($this->chemin),
        };

        if (! $source) {
            throw new RuntimeException("Cette image est abîmée ou incomplète : nous n'arrivons pas à la lire. Réessayez avec le fichier d'origine.");
        }

        // Le même rectangle, dans l'original non redressé.
        [$rx, $ry, $rl, $rh] = match ($this->angle) {
            180 => [$this->brutL - $x - $l, $this->brutH - $y - $h, $l, $h],
            -90 => [$y, $this->brutH - $x - $l, $h, $l],
            90 => [$this->brutL - $y - $h, $x, $h, $l],
            default => [$x, $y, $l, $h],
        };

        [$cl, $ch] = abs($this->angle) === 90 ? [$versH, $versL] : [$versL, $versH];

        $cible = imagecreatetruecolor($cl, $ch);
        // Une zone transparente (PNG) devient blanche, pas noire.
        imagefill($cible, 0, 0, imagecolorallocate($cible, 255, 255, 255));
        imagecopyresampled($cible, $source, 0, 0, $rx, $ry, $cl, $ch, $rl, $rh);
        unset($source);

        if ($this->angle !== 0) {
            $cible = imagerotate($cible, $this->angle, 0) ?: $cible;
        }

        return $cible;
    }

    /**
     * L'orientation EXIF, en angle GD (sens inverse des aiguilles d'une
     * montre). Les photos de téléphone la portent : sans elle, une photo prise
     * à la verticale s'affiche couchée. Les orientations miroir (2, 4, 5, 7)
     * ne sortent d'aucun appareil courant.
     */
    private static function angle(string $chemin, int $type): int
    {
        if ($type !== IMAGETYPE_JPEG) {
            return 0;
        }

        return match (self::orientation($chemin)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
    }

    /**
     * Lit l'étiquette d'orientation (0x0112) dans le bloc EXIF du JPEG.
     *
     * **Sans l'extension `exif`**, que l'image PHP du projet n'embarque
     * pas : le redressement s'appuyait sur `exif_read_data`, protégé par un
     * `function_exists` — il ne s'est donc jamais fait, et toute photo prise à
     * la verticale sortait couchée, sans une erreur nulle part. Lire une
     * étiquette dans un en-tête tient en quelques lignes ; une dépendance de
     * plus à l'image Docker, non.
     */
    private static function orientation(string $chemin): int
    {
        $f = @fopen($chemin, 'rb');
        if (! $f) {
            return 1;
        }

        $tete = (string) fread($f, 131072);
        fclose($f);

        if (! str_starts_with($tete, "\xFF\xD8")) {
            return 1;
        }

        $i = 2;
        $n = strlen($tete);

        while ($i + 4 <= $n && $tete[$i] === "\xFF") {
            $marqueur = ord($tete[$i + 1]);
            $longueur = unpack('n', substr($tete, $i + 2, 2))[1];

            // Début des données de l'image : plus aucun en-tête après.
            if ($marqueur === 0xDA) {
                break;
            }

            if ($marqueur === 0xE1 && substr($tete, $i + 4, 6) === "Exif\0\0") {
                return self::orientationTiff(substr($tete, $i + 10, $longueur - 8));
            }

            $i += 2 + $longueur;
        }

        return 1;
    }

    private static function orientationTiff(string $tiff): int
    {
        $ordre = substr($tiff, 0, 2);
        if (strlen($tiff) < 8 || ($ordre !== 'II' && $ordre !== 'MM')) {
            return 1;
        }

        $court = $ordre === 'II' ? 'v' : 'n';
        $long = $ordre === 'II' ? 'V' : 'N';

        $ifd = unpack($long, substr($tiff, 4, 4))[1] ?? 0;
        if ($ifd + 2 > strlen($tiff)) {
            return 1;
        }

        $entrees = unpack($court, substr($tiff, $ifd, 2))[1];

        for ($k = 0; $k < $entrees; $k++) {
            $entree = $ifd + 2 + $k * 12;
            if ($entree + 12 > strlen($tiff)) {
                break;
            }

            if (unpack($court, substr($tiff, $entree, 2))[1] === 0x0112) {
                $valeur = unpack($court, substr($tiff, $entree + 8, 2))[1];

                return $valeur >= 1 && $valeur <= 8 ? $valeur : 1;
            }
        }

        return 1;
    }

    /**
     * Quatre octets par pixel pour l'original, un peu de marge pour le
     * décodeur, et de quoi faire tourner l'application autour.
     */
    private function accorderMemoire(): void
    {
        set_time_limit(120);

        $actuelle = $this->octets((string) ini_get('memory_limit'));
        if ($actuelle < 0) {
            return;
        }

        $besoin = (int) ($this->brutL * $this->brutH * 5 + 192 * 1024 * 1024);

        if ($besoin > $actuelle) {
            ini_set('memory_limit', (string) (int) ceil($besoin / 1024 / 1024).'M');
        }
    }

    private function octets(string $valeur): int
    {
        $valeur = trim($valeur);
        if ($valeur === '' || $valeur === '-1') {
            return -1;
        }

        $n = (int) $valeur;

        return match (strtolower(substr($valeur, -1))) {
            'g' => $n * 1024 ** 3,
            'm' => $n * 1024 ** 2,
            'k' => $n * 1024,
            default => $n,
        };
    }
}
