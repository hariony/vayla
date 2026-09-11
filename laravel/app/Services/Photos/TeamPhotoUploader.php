<?php

namespace App\Services\Photos;

use App\Contracts\Destinations\DestinationGallery;
use App\Contracts\Office\ActionJournal;
use App\Contracts\Photos\PhotoProcessor;
use App\Contracts\Repositories\DestinationRepositoryInterface;
use App\Contracts\Repositories\PhotoLibraryRepositoryInterface;
use App\DTOs\Photos\UploadedTeamPhoto;
use App\DTOs\Photos\UploadTeamPhotoDto;
use App\Enums\AdminActionKind;
use App\Exceptions\OfficeRefusal;
use App\Models\Admin;
use App\Models\Destination;
use App\Models\Photo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Téléverser une photo de lieu — depuis la photothèque ou depuis la page d'une
 * destination : **un seul chemin** produit, crédite et range une photo.
 *
 * **Dans `images/destinations/`, jamais dans `lieux/`** : ce dernier
 * appartient à `PhotoSeeder` et au catalogue Commons, que `PhotoFilesTest`
 * compare au disque fichier par fichier. Sans destination, la photo attend
 * dans la photothèque — et n'est pas créditée au pied de page tant qu'elle
 * n'illustre rien (`PhotoRepository::credited`).
 */
final class TeamPhotoUploader
{
    public function __construct(
        private PhotoProcessor $traitement,
        private PhotoLibraryRepositoryInterface $photos,
        private DestinationRepositoryInterface $destinations,
        private DestinationGallery $galerie,
        private ActionJournal $journal,
    ) {}

    public function televerser(Admin $admin, UploadTeamPhotoDto $demande): UploadedTeamPhoto
    {
        $destination = $demande->destinationId ? $this->destinations->findById($demande->destinationId) : null;
        $cle = $this->cle($destination?->slug ?? $demande->credit->caption);

        $photo = $this->photos->creerPhotoEquipe($cle, $this->produire($demande->fichier, $cle), $demande->credit);

        $destination ? $this->accrocher($admin, $destination, $photo) : $this->consigner($admin, $photo);

        return new UploadedTeamPhoto($photo, $destination);
    }

    private function produire(UploadedFile $fichier, string $cle): int
    {
        try {
            return $this->traitement->produire($fichier, 'destinations', $cle);
        } catch (RuntimeException $e) {
            throw new OfficeRefusal($e->getMessage());
        }
    }

    /** Une clé qui dit ce que montre la photo, et qu'on ne devine pas. */
    private function cle(string $texte): string
    {
        return (Str::slug(Str::limit($texte, 32, '')) ?: 'photo').'-'.Str::lower(Str::random(8));
    }

    private function accrocher(Admin $admin, Destination $destination, Photo $photo): void
    {
        $this->galerie->ajouter($destination, $photo);

        $this->journal->consigner($admin, AdminActionKind::DestinationSaved, $destination,
            "« {$destination->name} » : photo ajoutée, de {$photo->author} ({$photo->licence}).");
    }

    private function consigner(Admin $admin, Photo $photo): void
    {
        $this->journal->consigner($admin, AdminActionKind::PhotoUploaded, $photo,
            "Photo « {$photo->caption} » ajoutée à la photothèque, de {$photo->author} ({$photo->licence}).");
    }
}
