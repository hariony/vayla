<?php

namespace App\Contracts\Office;

use App\Models\Admin;

/**
 * Les mots de passe **provisoires** de l'équipe : vus par quelqu'un d'autre,
 * ils ne rouvrent que « Mon compte » jusqu'à ce que la personne en choisisse
 * un. Jamais envoyés par e-mail — un mot de passe dans une boîte y reste.
 */
interface AdminPasswords
{
    /** La personne choisit le sien : le back-office s'ouvre en entier. */
    public function choisir(Admin $admin, string $motDePasse): void;

    /** Pose un mot de passe provisoire et le rend, pour être dicté une fois. */
    public function provisoire(Admin $admin): string;

    public function reinitialiser(Admin $par, Admin $membre): string;
}
