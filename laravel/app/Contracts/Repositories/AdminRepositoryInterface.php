<?php

namespace App\Contracts\Repositories;

use App\Models\Admin;
use Illuminate\Support\Collection;

/** Les membres de l'équipe. */
interface AdminRepositoryInterface
{
    public function nom(int $id): ?string;

    /** @return Collection<int, Admin> par nom, avec le nombre de gestes au journal (`actions_count`) */
    public function tousParNom(): Collection;

    public function nombre(): int;

    public function creer(string $nom, string $email): Admin;

    public function supprimer(Admin $admin): void;

    /** L'adresse est comparée telle quelle : elle arrive déjà en minuscules. */
    public function parEmail(string $email): ?Admin;

    public function marquerConnexion(Admin $admin): void;

    /**
     * `$choisi` : la personne l'a choisi elle-même. Sinon il est provisoire —
     * vu par quelqu'un d'autre — et `password_set_at` reste nul.
     */
    public function poserMotDePasse(Admin $admin, string $motDePasse, bool $choisi): void;
}
