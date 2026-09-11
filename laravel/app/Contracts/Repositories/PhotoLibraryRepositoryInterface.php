<?php

namespace App\Contracts\Repositories;

use App\Data\Photos\PhotoUsageData;
use App\DTOs\Photos\PhotoCreditDto;
use App\DTOs\Photos\PhotoCreditPatch;
use App\Enums\PhotoLibraryTab;
use App\Models\Photo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Les données de la photothèque du back-office. Séparé de
 * `PhotoRepositoryInterface`, qui sert la lecture publique : l'écran de l'équipe
 * n'a rien à imposer au site.
 */
interface PhotoLibraryRepositoryInterface
{
    /** @return LengthAwarePaginator<int, Photo> les téléversées d'abord, les plus récentes en tête */
    public function paginer(PhotoLibraryTab $onglet, string $recherche, int $parPage): LengthAwarePaginator;

    public function compter(PhotoLibraryTab $onglet): int;

    public function trouver(int $id): ?Photo;

    /**
     * Où chaque photo apparaît : galeries de destinations et d'annonces.
     *
     * @param  array<int, int>  $ids
     * @return array<int, array<int, PhotoUsageData>> indexé par identifiant de photo
     */
    public function usages(array $ids): array;

    /** Une photo de lieu téléversée par l'équipe, dans `destinations/`. */
    public function creerPhotoEquipe(string $cle, int $largeur, PhotoCreditDto $credit): Photo;

    /**
     * Applique la correction et renvoie les colonnes réellement modifiées.
     *
     * @return array<int, string>
     */
    public function appliquerCredit(Photo $photo, PhotoCreditPatch $patch): array;

    public function supprimer(Photo $photo): void;
}
