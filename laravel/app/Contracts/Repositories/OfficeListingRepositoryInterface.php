<?php

namespace App\Contracts\Repositories;

use App\Enums\ListingStatus;
use App\Enums\TrustLevel;
use App\Models\Listing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/** Les annonces, vues par l'équipe qui les vérifie. */
interface OfficeListingRepositoryInterface
{
    /** @return array<string, int> par statut */
    public function comptesParStatut(): array;

    public function nombreAuStatut(ListingStatus $statut): int;

    /** @return LengthAwarePaginator<int, Listing> les plus anciennes à vérifier d'abord */
    public function paginer(?ListingStatus $statut, ?string $recherche, int $parPage): LengthAwarePaginator;

    /** @return array<int, int> les annonces en ligne, par niveau de confiance */
    public function echelleEnLigne(): array;

    /** @return Collection<int, Listing> */
    public function aVerifier(int $limite): Collection;

    /** Charge ce que l'écran de modération lit : destination, propriétaire, photos, équipements, confirmations. */
    public function pourModeration(Listing $listing): Listing;

    /** Les demandes et séjours acceptés qui ne sont pas encore passés. */
    public function nombreAVenir(Listing $listing): int;

    public function nombreConfirmations(Listing $listing): int;

    /** En ligne ; le motif d'un renvoi précédent s'efface. */
    public function publier(Listing $listing): void;

    /** Retour en brouillon, avec le motif que le propriétaire lira en tête de sa fiche. */
    public function renvoyer(Listing $listing, string $motif): void;

    /** Retirée du site ; le reste de la fiche ne bouge pas. */
    public function archiver(Listing $listing): void;

    public function changerNiveau(Listing $listing, TrustLevel $niveau): void;
}
