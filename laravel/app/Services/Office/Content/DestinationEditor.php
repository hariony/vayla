<?php

namespace App\Services\Office\Content;

use App\Contracts\Office\ActionJournal;
use App\Contracts\Repositories\OfficeDestinationRepositoryInterface;
use App\DTOs\Content\DestinationDto;
use App\Enums\AdminActionKind;
use App\Exceptions\OfficeRefusal;
use App\Models\Admin;
use App\Models\Destination;
use App\Services\Support\UniqueSlug;

/**
 * Créer, modifier, supprimer une destination. **Le slug naît du nom et ne
 * bouge plus** : c'est l'adresse de sa page et le filtre du catalogue. **La
 * photo ne s'écrit pas ici** : elle vient de la galerie, dont
 * `DestinationGallery::synchroniserCouverture()` est le seul écrivain.
 */
final class DestinationEditor
{
    public function __construct(
        private OfficeDestinationRepositoryInterface $destinations,
        private UniqueSlug $slugs,
        private ActionJournal $journal,
    ) {}

    public function creer(Admin $admin, DestinationDto $saisie): Destination
    {
        $slug = $this->slugs->pour($saisie->name, fn (string $s) => $this->destinations->slugExiste($s));
        $destination = $this->destinations->creer($slug, $saisie);

        $this->journal->consigner($admin, AdminActionKind::DestinationSaved, $destination, "Destination « {$destination->name} » créée.");

        return $destination;
    }

    public function modifier(Admin $admin, Destination $destination, DestinationDto $saisie): void
    {
        $this->destinations->modifier($destination, $saisie);

        $this->journal->consigner($admin, AdminActionKind::DestinationSaved, $destination, "Destination « {$destination->name} » modifiée.");
    }

    /** **On ne supprime pas ce qui porte des logements** : ils resteraient sans lieu. */
    public function supprimer(Admin $admin, Destination $destination): void
    {
        $n = $this->destinations->nombreAnnonces($destination);

        if ($n > 0) {
            throw new OfficeRefusal("« {$destination->name} » porte {$n} logement".($n > 1 ? 's' : '').' : la supprimer les laisserait sans lieu. Rattachez-les ailleurs d’abord.');
        }

        $this->journal->consigner($admin, AdminActionKind::DestinationDeleted, $destination, "Destination « {$destination->name} » supprimée.");
        $this->destinations->supprimer($destination);
    }
}
