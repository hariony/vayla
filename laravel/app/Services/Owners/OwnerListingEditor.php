<?php

namespace App\Services\Owners;

use App\Contracts\Listings\ListingGallery;
use App\DTOs\Listings\ListingDraftDto;
use App\Exceptions\ListingNotFoundException;
use App\Exceptions\PhotoRefusedException;
use App\Models\Listing;
use App\Models\Owner;
use App\Services\OwnerListingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Les gestes du propriétaire sur ses annonces — **toujours les siennes**
 * (`OwnerSpace`) : créer, corriger, envoyer à Vayla, ranger sa galerie.
 */
final class OwnerListingEditor
{
    public function __construct(
        private OwnerSpace $portee,
        private OwnerListingService $redaction,
        private ListingGallery $galerie,
    ) {}

    public function creer(Owner $owner, ListingDraftDto $saisie): Listing
    {
        return DB::transaction(function () use ($owner, $saisie) {
            $listing = $this->redaction->creer($owner, $saisie->fiche);
            $this->redaction->poserEquipements($listing, $saisie->equipements);

            return $listing;
        });
    }

    /**
     * Les équipements font partie de ce que Vayla est allé vérifier : on ne
     * les touche plus une fois l'annonce contrôlée.
     *
     * @throws ListingNotFoundException
     */
    public function modifier(Owner $owner, string $slug, ListingDraftDto $saisie): void
    {
        $listing = $this->portee->logement($owner, $slug);

        DB::transaction(function () use ($listing, $saisie) {
            $this->redaction->modifier($listing, $saisie->fiche);

            if ($listing->status->estModifiable()) {
                $this->redaction->poserEquipements($listing, $saisie->equipements);
            }
        });
    }

    /** @return list<string> ce qui manque encore ; vide si la fiche est partie */
    public function soumettre(Owner $owner, string $slug): array
    {
        return $this->redaction->soumettre($this->portee->logement($owner, $slug));
    }

    /** @throws PhotoRefusedException si l'image est illisible ou trop petite — message à montrer tel quel */
    public function ajouterPhoto(Owner $owner, string $slug, UploadedFile $fichier, ?string $legende): void
    {
        $this->galerie->ajouter($this->portee->logement($owner, $slug), $fichier, $legende);
    }

    /** Une photo qui n'est pas dans cette galerie ne se retire pas depuis cette galerie. */
    public function retirerPhoto(Owner $owner, string $slug, int $photoId): void
    {
        $listing = $this->portee->logement($owner, $slug);
        $photo = $listing->photos->firstWhere('id', $photoId) ?? throw new ListingNotFoundException($slug);

        $this->galerie->retirer($listing, $photo);
    }

    /** @param  list<int>  $ids  dans l'ordre voulu ; la première est la couverture */
    public function ordonnerPhotos(Owner $owner, string $slug, array $ids): void
    {
        $this->galerie->reordonner($this->portee->logement($owner, $slug), $ids);
    }
}
