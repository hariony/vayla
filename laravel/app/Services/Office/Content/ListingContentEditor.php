<?php

namespace App\Services\Office\Content;

use App\Contracts\Listings\ListingDrafting;
use App\Contracts\Office\ActionJournal;
use App\Contracts\Repositories\OfficeCategoryRepositoryInterface;
use App\Contracts\Repositories\OfficeListingContentRepositoryInterface;
use App\DTOs\Listings\ListingContentDto;
use App\Enums\AdminActionKind;
use App\Models\Admin;
use App\Models\Listing;
use App\Models\Owner;
use Illuminate\Support\Facades\DB;

/**
 * Le contenu d'une annonce, **tout le contenu** — y compris ce que le
 * propriétaire ne peut plus toucher après vérification. C'est justement le
 * rôle de Vayla : corriger une capacité mal saisie à l'appel. Le journal le
 * garde, champ par champ. `trust_level`, `status` et `slug` n'entrent pas par
 * là : ils ont leurs gestes à eux.
 */
final class ListingContentEditor
{
    /** Les libellés des champs, pour dire au journal ce qui a changé. */
    private const CHAMPS = [
        'title' => 'titre', 'destination_id' => 'destination', 'kind' => 'type', 'summary' => 'accroche',
        'description' => 'description', 'guests' => 'capacité', 'bedrooms' => 'chambres', 'beds' => 'couchages',
        'bathrooms' => 'salles d’eau', 'surface' => 'surface', 'price' => 'tarif', 'min_nights' => 'nuits minimum',
        'max_nights' => 'nuits maximum', 'check_in_from' => 'heure d’arrivée', 'check_out_before' => 'heure de départ',
        'pets_allowed' => 'animaux', 'smoking_allowed' => 'fumeurs', 'events_allowed' => 'fêtes', 'featured' => 'mise en avant',
        'equipements' => 'équipements', 'categories' => 'catégories',
    ];

    public function __construct(
        private ListingDrafting $redaction,
        private OfficeListingContentRepositoryInterface $contenu,
        private OfficeCategoryRepositoryInterface $categories,
        private ActionJournal $journal,
    ) {}

    /** @return array<int, string> ce qui a changé, en mots */
    public function modifier(Admin $admin, Listing $listing, ListingContentDto $saisie): array
    {
        $changes = DB::transaction(fn () => [
            ...$this->contenu->modifierFiche($listing, $saisie->fiche),
            ...$this->poserEquipements($listing, $saisie),
            ...$this->poserCategories($listing, $saisie->categories),
        ]);

        $mots = array_map(fn (string $c) => self::CHAMPS[$c] ?? $c, $changes);

        if ($mots !== []) {
            $this->journal->consigner($admin, AdminActionKind::ListingEdited, $listing, "« {$listing->title} » : ".implode(', ', $mots).'.');
        }

        return $mots;
    }

    /**
     * Une annonce saisie **pour** un propriétaire qui la dicte au téléphone.
     * Elle naît comme les autres : en brouillon, au niveau 1. La saisir n'est
     * pas la vérifier.
     */
    public function creer(Admin $admin, Owner $owner, ListingContentDto $saisie): Listing
    {
        $listing = DB::transaction(function () use ($owner, $saisie) {
            $listing = $this->redaction->creer($owner, $saisie->fiche);
            $this->redaction->poserEquipements($listing, $saisie->equipements);
            $this->poserCategories($listing, $saisie->categories);

            return $listing;
        });

        $this->journal->consigner($admin, AdminActionKind::ListingCreated, $listing, "« {$listing->title} » saisie pour {$owner->name}, en brouillon.");

        return $listing;
    }

    /** @return array<int, string> */
    private function poserEquipements(Listing $listing, ListingContentDto $saisie): array
    {
        $avant = $this->contenu->empreinteEquipements($listing);
        $this->redaction->poserEquipements($listing, $saisie->equipements);

        return $avant !== $this->contenu->empreinteEquipements($listing) ? ['equipements'] : [];
    }

    /**
     * Seulement les catégories éditoriales : « Séjour confirmé » se déduit du
     * niveau 4, « Tout » n'est pas une étiquette.
     *
     * @param  array<int, int>  $ids
     * @return array<int, string>
     */
    private function poserCategories(Listing $listing, array $ids): array
    {
        return $this->contenu->poserCategories($listing, $this->categories->idsEditoriaux($ids)) ? ['categories'] : [];
    }
}
