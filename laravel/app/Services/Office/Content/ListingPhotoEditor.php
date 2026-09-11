<?php

namespace App\Services\Office\Content;

use App\Contracts\Listings\ListingGallery;
use App\Contracts\Office\ActionJournal;
use App\Contracts\Repositories\OfficeListingContentRepositoryInterface;
use App\Enums\AdminActionKind;
use App\Exceptions\OfficeRefusal;
use App\Models\Admin;
use App\Models\Listing;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/** Les photos d'une annonce, corrigées par l'équipe : remplacer une photo floue, ranger la galerie. */
final class ListingPhotoEditor
{
    public function __construct(
        private ListingGallery $galerie,
        private OfficeListingContentRepositoryInterface $contenu,
        private ActionJournal $journal,
    ) {}

    public function ajouter(Admin $admin, Listing $listing, UploadedFile $fichier, ?string $legende): void
    {
        try {
            $this->galerie->ajouter($listing, $fichier, $legende);
        } catch (RuntimeException $e) {
            throw new OfficeRefusal($e->getMessage());
        }

        $this->journal->consigner($admin, AdminActionKind::ListingEdited, $listing, "« {$listing->title} » : photo ajoutée.");
    }

    public function retirer(Admin $admin, Listing $listing, int $photoId): void
    {
        // L'identifiant vient du navigateur : une photo d'une autre galerie ne se retire pas d'ici.
        $photo = $this->contenu->photoDeLaGalerie($listing, $photoId)
            ?? throw new OfficeRefusal('Cette photo n’est pas dans la galerie de l’annonce.');

        $this->galerie->retirer($listing, $photo);
        $this->journal->consigner($admin, AdminActionKind::ListingEdited, $listing, "« {$listing->title} » : photo retirée.");
    }

    /** @param  array<int, int>  $ids */
    public function ordonner(Admin $admin, Listing $listing, array $ids): void
    {
        $this->galerie->reordonner($this->contenu->charger($listing), $ids);
        $this->journal->consigner($admin, AdminActionKind::ListingEdited, $listing, "« {$listing->title} » : ordre des photos, nouvelle couverture possible.");
    }
}
