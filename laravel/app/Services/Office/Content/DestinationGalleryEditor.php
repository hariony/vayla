<?php

namespace App\Services\Office\Content;

use App\Contracts\Destinations\DestinationGallery;
use App\Contracts\Office\ActionJournal;
use App\Contracts\Photos\PhotoStorage;
use App\Contracts\Repositories\DestinationGalleryRepositoryInterface;
use App\Contracts\Repositories\PhotoLibraryRepositoryInterface;
use App\Enums\AdminActionKind;
use App\Exceptions\OfficeRefusal;
use App\Models\Admin;
use App\Models\Destination;
use App\Models\Photo;
use Illuminate\Support\Facades\DB;

/**
 * Composer la galerie d'une destination. **La première photo est la
 * couverture** — celle de l'atlas et de l'en-tête — et il n'y a pas d'autre
 * bouton pour la désigner : la même règle que les annonces.
 */
final class DestinationGalleryEditor
{
    public function __construct(
        private DestinationGalleryRepositoryInterface $galeries,
        private DestinationGallery $galerie,
        private PhotoLibraryRepositoryInterface $photos,
        private PhotoStorage $stockage,
        private ActionJournal $journal,
    ) {}

    /** **Seulement une vraie photographie de lieu** : une destination est un lieu réel. */
    public function ajouterDeLaPhototheque(Admin $admin, Destination $destination, int $photoId): void
    {
        $photo = $this->galeries->photoDeLieu($photoId)
            ?? throw new OfficeRefusal('Cette photo ne peut pas illustrer une destination : seulement de vraies photographies de lieux, créditées.');

        if ($this->galeries->contient($destination, $photo->id)) {
            throw new OfficeRefusal('Cette photo est déjà dans la galerie.');
        }

        $this->galerie->ajouter($destination, $photo);
        $this->journal->consigner($admin, AdminActionKind::DestinationSaved, $destination, "« {$destination->name} » : « {$photo->caption} » ajoutée à la galerie.");
    }

    /** @param  array<int, int>  $ids  l'ordre voulu ; un identifiant venu d'ailleurs est ignoré */
    public function ordonner(Admin $admin, Destination $destination, array $ids): void
    {
        $siennes = $this->galeries->photos($destination);
        $avant = $siennes[0] ?? null;
        $ordre = array_values(array_intersect(array_map('intval', $ids), $siennes));

        DB::transaction(function () use ($destination, $ordre, $siennes) {
            $this->galeries->ordonner($destination, [...$ordre, ...array_diff($siennes, $ordre)]);
            $this->galerie->synchroniserCouverture($destination);
        });

        $couverture = ($this->galeries->photos($destination)[0] ?? null) !== $avant ? ' — nouvelle couverture' : '';
        $this->journal->consigner($admin, AdminActionKind::DestinationSaved, $destination, "« {$destination->name} » : galerie rangée{$couverture}.");
    }

    /**
     * Une photo **téléversée** que plus aucune destination ne montre est
     * effacée avec ses fichiers : une image qui traîne serait encore créditée
     * au pied de page. Une photographie de Commons est seulement détachée.
     */
    public function retirer(Admin $admin, Destination $destination, int $photoId): void
    {
        $photo = $this->galeries->galerie($destination)->firstWhere('id', $photoId)
            ?? throw new OfficeRefusal('Cette photo n’est pas dans la galerie de la destination.');

        DB::transaction(function () use ($destination, $photo) {
            $this->galeries->detacher($destination, $photo->id);
            $this->galerie->renumeroter($destination);
            $this->galerie->synchroniserCouverture($destination);
        });

        $effacee = $this->effacerSiOrpheline($photo);

        $this->journal->consigner($admin, AdminActionKind::DestinationSaved, $destination,
            "« {$destination->name} » : « {$photo->caption} » retirée de la galerie".($effacee ? ', fichiers effacés.' : '.'));
    }

    private function effacerSiOrpheline(Photo $photo): bool
    {
        if ($photo->folder !== 'destinations' || $this->galeries->estMontree($photo->id)) {
            return false;
        }

        $this->stockage->effacer($photo);
        $this->photos->supprimer($photo);

        return true;
    }
}
