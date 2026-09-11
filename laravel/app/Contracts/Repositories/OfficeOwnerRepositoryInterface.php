<?php

namespace App\Contracts\Repositories;

use App\Models\Owner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/** Les propriétaires, vus par l'équipe. */
interface OfficeOwnerRepositoryInterface
{
    /** @return LengthAwarePaginator<int, Owner> par nom, avec leurs comptes d'annonces et de réservations */
    public function paginer(bool $numeroAVerifier, ?string $recherche, int $parPage): LengthAwarePaginator;

    public function total(): int;

    public function nombreNumeroAVerifier(): int;

    /** Ceux dont une fiche attend l'appel et dont le numéro n'est pas encore confirmé : le vrai travail du jour. */
    public function nombreAAppeler(): int;

    /** Charge ce que la fiche lit : ses annonces, avec destination et photos. */
    public function pourFiche(Owner $owner): Owner;

    /** @return Collection<int, Owner> par nom */
    public function tousParNom(): Collection;

    public function trouver(int $id): ?Owner;

    public function marquerNumeroVerifie(Owner $owner): void;
}
