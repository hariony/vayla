<?php

namespace App\Contracts\Repositories;

use App\DTOs\Auth\TravellerAccountDto;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Les comptes voyageurs. **Les séjours s'y rattachent par l'adresse e-mail** :
 * celui qui a réservé avant de créer son compte les retrouve dès l'inscription.
 */
interface TravellerRepositoryInterface
{
    public function parEmail(string $email): ?User;

    /** Ni nom ni mot de passe : le nom est demandé à la demande de séjour, le mot de passe n'existe plus. */
    public function creer(string $email): User;

    public function marquerEmailVerifie(User $user): void;

    public function modifierCompte(User $user, TravellerAccountDto $compte): void;

    /** @return Collection<int, Booking> la plus lointaine arrivée en tête, avec l'annonce et sa destination */
    public function reservations(string $email): Collection;

    public function nombreSejours(string $email): int;
}
