<?php

namespace App\Services;

use App\Contracts\Repositories\OwnerRepositoryInterface;
use App\Exceptions\PhotoRefusedException;
use App\Models\Owner;
use App\Services\Images\ImageSource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Le portrait d'un propriétaire.
 *
 * **Il ne passe pas par `PhotoUploadService`, et c'est délibéré.** Celui-ci
 * sert les photos d'annonce : recadrage 4/3, trois résolutions jusqu'à
 * 3200 px, refus en dessous de 1200, et une ligne dans la table `photos` que
 * `PhotoFilesTest` surveille — crédit obligatoire, aucun fichier orphelin. Un
 * portrait n'a ni crédit, ni légende, ni partage entre deux comptes : l'y
 * faire entrer aurait créé un orphelin sans auteur à chaque téléversement.
 *
 * **Carré, et petit.** Un portrait s'affiche à 2 rem dans une barre latérale et
 * à 4 rem sur son propre écran : 160 px suffisent, 480 couvrent les écrans
 * denses. Produire un 1600 px de plus serait un fichier que personne ne
 * télécharge, sur des connexions où chaque aller-retour se paie.
 *
 * **Le seuil est bas — 200 px.** La règle des 1200 px protège une photo de
 * tête qu'on regarde en grand ; un portrait de profil pris dans une
 * conversation WhatsApp reste parfaitement lisible à 2 rem, et refuser
 * l'unique photo que quelqu'un a de lui-même serait refuser le portrait tout
 * court.
 *
 * **L'ancien fichier est effacé au remplacement.** Un portrait n'appartient
 * qu'à un compte : le laisser sur le disque accumulerait des visages que plus
 * rien ne nomme — et un visage est une donnée personnelle, pas un octet.
 */
class OwnerPortraitService
{
    public function __construct(
        private OwnerRepositoryInterface $proprietaires,
    ) {}

    /** @var array<int, int> */
    private const PALIERS = [160, 480];

    /** En dessous, même une pastille de 2 rem se voit floue. */
    private const COTE_MINIMAL = 200;

    private const QUALITE = 84;

    public function dossier(): string
    {
        return public_path('images/proprietaires');
    }

    /**
     * Pose le portrait, remplace le précédent, et rend sa clé.
     *
     * @throws PhotoRefusedException si l'image est illisible ou trop petite — message à montrer tel quel
     */
    public function poser(Owner $owner, UploadedFile $fichier): string
    {
        // Lue sans être recopiée en pleine taille : un portrait pris avec un
        // téléphone récent fait 48 Mpx, et le décodage complet dépassait la
        // mémoire de PHP.
        try {
            $source = ImageSource::ouvrir($fichier->getRealPath());
        } catch (RuntimeException $e) {
            throw new PhotoRefusedException($e->getMessage(), previous: $e);
        }

        $cote = min($source->largeur, $source->hauteur);

        if ($cote < self::COTE_MINIMAL) {
            throw new PhotoRefusedException(
                "Cette image fait {$cote} pixels de côté : il en faut au moins "
                .self::COTE_MINIMAL.'. Prenez la photo avec l’appareil du téléphone '
                .'plutôt que dans une conversation, qui les réduit beaucoup.'
            );
        }

        // Recadrage carré **centré haut**, pas centré tout court : sur un
        // portrait en pied, un carré pris au milieu de l'image coupe la tête.
        // On garde le tiers supérieur, là où se trouve un visage dans presque
        // toutes les photos qu'on reçoit.
        $taille = min(max(self::PALIERS), $cote);
        $carre = $source->extraire(
            (int) round(($source->largeur - $cote) / 2), max(0, (int) round(($source->hauteur - $cote) / 4)), $cote, $cote,
            $taille, $taille,
        );

        // Une clé neuve à chaque fois : un navigateur qui garde l'ancienne en
        // cache afficherait le visage précédent pendant des jours.
        $cle = $owner->id.'-'.Str::lower(Str::random(12));

        $this->ecrire($carre, $cle);

        $ancien = $owner->portrait;

        $this->proprietaires->poserPortrait($owner, $cle);

        if ($ancien) {
            $this->effacer($ancien);
        }

        return $cle;
    }

    /** Retire le portrait du compte **et du disque**. */
    public function retirer(Owner $owner): void
    {
        if (! $owner->portrait) {
            return;
        }

        $this->effacer($owner->portrait);

        $this->proprietaires->poserPortrait($owner, null);
    }

    private function effacer(string $cle): void
    {
        foreach (self::PALIERS as $cote) {
            $chemin = $this->dossier()."/{$cle}-{$cote}.webp";

            if (is_file($chemin)) {
                unlink($chemin);
            }
        }
    }

    /** **Jamais d'agrandissement** : on s'arrête à la taille réelle. */
    private function ecrire(\GdImage $carre, string $cle): void
    {
        if (! is_dir($this->dossier())) {
            mkdir($this->dossier(), 0o755, true);
        }

        // Du plus grand au plus petit, chacun réduit depuis le précédent.
        $paliers = self::PALIERS;
        rsort($paliers);

        foreach ($paliers as $cible) {
            $taille = min($cible, imagesx($carre));

            if (imagesx($carre) !== $taille) {
                $vignette = imagecreatetruecolor($taille, $taille);
                imagecopyresampled($vignette, $carre, 0, 0, 0, 0, $taille, $taille, imagesx($carre), imagesy($carre));
                $carre = $vignette;
            }

            imagewebp($carre, $this->dossier()."/{$cle}-{$cible}.webp", self::QUALITE);
        }
    }
}
