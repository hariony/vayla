<?php

namespace App\Contracts\Repositories;

use App\DTOs\Listings\AmenityChoiceDto;
use App\DTOs\Listings\ListingFicheDto;
use App\Models\Amenity;
use App\Models\Destination;
use App\Models\Listing;
use App\Models\Owner;
use Illuminate\Support\Collection;

/** La rédaction d'une annonce : sa fiche, ses équipements, son envoi à Vayla. */
interface ListingDraftRepositoryInterface
{
    /** @return Collection<int, Destination> par nom */
    public function destinations(): Collection;

    /** @return Collection<int, Amenity> dans l'ordre du vocabulaire */
    public function equipements(): Collection;

    public function slugPris(string $slug): bool;

    /** En brouillon, au niveau 1 : une annonce neuve part de « déclarée », quoi qu'on poste. */
    public function creer(Owner $owner, ListingFicheDto $fiche, string $slug): Listing;

    /**
     * @param  list<string>|null  $seulement  les colonnes à écrire ; `null` : toute la fiche
     */
    public function modifierFiche(Listing $listing, ListingFicheDto $fiche, ?array $seulement = null): void;

    /** @param  list<AmenityChoiceDto>  $choix */
    public function poserEquipements(Listing $listing, array $choix): void;

    public function nombrePhotos(Listing $listing): int;

    public function nombreEquipements(Listing $listing): int;

    /** Envoyée à Vayla ; le motif d'un renvoi précédent a servi, il s'efface. */
    public function soumettre(Listing $listing): void;

    /** Avec ses équipements et ses photos, pour le formulaire. */
    public function pourEdition(Listing $listing): Listing;
}
