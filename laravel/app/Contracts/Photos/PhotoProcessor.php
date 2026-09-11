<?php

namespace App\Contracts\Photos;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Transformer un fichier reçu en photo du site : recadrée en 4/3, écrite en
 * WebP à chaque palier que l'original permet, **jamais agrandie**.
 */
interface PhotoProcessor
{
    /** Les largeurs produites, du plus petit au plus grand. */
    public const PALIERS = [800, 1600, 3200];

    /**
     * Le poids maximal d'un fichier reçu, en kilo-octets : **40 Mo**, de quoi
     * accepter l'original d'un appareil de 48 Mpx. Le navigateur réduit
     * d'ordinaire la photo avant l'envoi (`Support/preparerPhoto.js`) ; cette
     * borne sert quand il n'a pas pu. PHP en accepte 50, nginx 100.
     */
    public const POIDS_MAX_KO = 40960;

    /**
     * Écrit les paliers dans `images/{dossier}/{cle}-{largeur}.webp` et renvoie
     * le plus grand réellement produit — ce que `photos.width` doit dire.
     *
     * @throws RuntimeException si l'image est illisible, trop petite ou démesurée — avec un message à montrer tel quel
     */
    public function produire(UploadedFile $fichier, string $dossier, string $cle): int;
}
