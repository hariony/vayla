<?php

namespace App\Repositories\Contracts;

use App\Enums\ListingStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Les lectures du back-office : des listes filtrées, des comptes.
 *
 * Séparées des dépôts du site parce qu'elles n'ont pas la même portée — le
 * site ne voit que ce qui est en ligne et non démonstratif selon la
 * configuration ; le back-office voit **tout**, brouillons et archives compris.
 * Mélanger les deux portées dans une même méthode, c'est le jour où un
 * brouillon apparaît dans le catalogue public.
 */
interface OfficeRepositoryInterface
{
    /** @return array<string, int> */
    public function comptesAnnonces(): array;

    public function annonces(?ListingStatus $statut, ?string $recherche, int $parPage = 25): LengthAwarePaginator;

    /** @return array<int, int> nombre d'annonces en ligne par niveau */
    public function echelleEnLigne(): array;

    public function annoncesAVerifier(int $limite): Collection;

    /** @return array<string, int> */
    public function comptesReservations(): array;

    public function reservations(string $filtre, ?string $recherche, int $parPage = 25): LengthAwarePaginator;

    public function demandesUrgentes(int $heures, int $limite): Collection;

    public function proprietaires(?string $filtre, ?string $recherche, int $parPage = 25): LengthAwarePaginator;

    public function voyageurs(?string $recherche, int $parPage = 30): LengthAwarePaginator;

    public function messagesWhatsApp(bool $envoyes, int $parPage = 30): LengthAwarePaginator;

    public function journal(?string $famille, int $parPage = 40): LengthAwarePaginator;
}
