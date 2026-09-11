<?php

namespace App\Services\Photos;

use App\Contracts\Office\ActionJournal;
use App\Contracts\Photos\PhotoStorage;
use App\Contracts\Repositories\PhotoLibraryRepositoryInterface;
use App\Enums\AdminActionKind;
use App\Exceptions\OfficeRefusal;
use App\Models\Admin;
use App\Models\Photo;

/**
 * Supprimer une photo téléversée **qui n'illustre plus rien**, fichiers
 * compris. Tout le reste a une raison de rester, et le refus la dit
 * (`PhotoRemovalPolicy`).
 */
final class PhotoRemover
{
    public function __construct(
        private PhotoLibraryRepositoryInterface $photos,
        private PhotoStorage $stockage,
        private PhotoRemovalPolicy $politique,
        private ActionJournal $journal,
    ) {}

    public function supprimer(Admin $admin, Photo $photo): void
    {
        $usages = $this->photos->usages([$photo->id])[$photo->id] ?? [];

        if ($raison = $this->politique->raison($photo, $usages)) {
            throw new OfficeRefusal($raison);
        }

        $this->stockage->effacer($photo);
        $this->journal->consigner($admin, AdminActionKind::PhotoDeleted, null, "Photo « {$photo->caption} » supprimée de la photothèque, fichiers compris.");
        $this->photos->supprimer($photo);
    }
}
