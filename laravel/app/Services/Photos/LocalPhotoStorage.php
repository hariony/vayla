<?php

namespace App\Services\Photos;

use App\Contracts\Photos\PhotoProcessor;
use App\Contracts\Photos\PhotoStorage;
use App\Data\Photos\PhotoFilesData;
use App\Models\Photo;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Les fichiers des photos, sous `public/images/{dossier}/`. Servis tels quels
 * par nginx : pas de disque Laravel devant, une photo est une URL statique.
 */
final class LocalPhotoStorage implements PhotoStorage
{
    public function dossier(string $dossier): string
    {
        return public_path("images/{$dossier}");
    }

    public function fichiers(Photo $photo): PhotoFilesData
    {
        $poids = 0;
        $paliers = [];

        foreach (PhotoProcessor::PALIERS as $largeur) {
            $chemin = $this->chemin($photo, $largeur);

            if (is_file($chemin)) {
                $poids += (int) filesize($chemin);
                $paliers[] = $largeur;
            }
        }

        return new PhotoFilesData($poids, $paliers);
    }

    public function poidsDossier(string $dossier): int
    {
        $racine = $this->dossier($dossier);

        if (! is_dir($racine)) {
            return 0;
        }

        $octets = 0;
        $fichiers = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($racine, FilesystemIterator::SKIP_DOTS));

        foreach ($fichiers as $fichier) {
            if ($fichier->isFile() && $fichier->getExtension() === 'webp') {
                $octets += $fichier->getSize();
            }
        }

        return $octets;
    }

    public function effacer(Photo $photo): void
    {
        foreach (PhotoProcessor::PALIERS as $largeur) {
            $chemin = $this->chemin($photo, $largeur);

            if (is_file($chemin)) {
                unlink($chemin);
            }
        }
    }

    private function chemin(Photo $photo, int $largeur): string
    {
        return $this->dossier($photo->folder)."/{$photo->key}-{$largeur}.webp";
    }
}
