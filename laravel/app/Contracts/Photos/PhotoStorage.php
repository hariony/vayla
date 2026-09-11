<?php

namespace App\Contracts\Photos;

use App\Data\Photos\PhotoFilesData;
use App\Models\Photo;

/** Où vivent les fichiers des photos, et ce qu'ils pèsent. */
interface PhotoStorage
{
    /** Le dossier absolu d'une provenance : `lieux`, `destinations`, `annonces`. */
    public function dossier(string $dossier): string;

    public function fichiers(Photo $photo): PhotoFilesData;

    /** Le poids de tous les fichiers WebP d'un dossier, en octets. */
    public function poidsDossier(string $dossier): int;

    /** Efface les fichiers d'une photo, tous paliers. */
    public function effacer(Photo $photo): void;
}
