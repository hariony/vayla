<?php

namespace App\DTOs\Office;

use App\Models\Admin;

/** Le résultat d'un ajout : le membre, et son mot de passe provisoire — à dicter une fois, jamais écrit ailleurs. */
final readonly class NewMember
{
    public function __construct(
        public Admin $membre,
        public string $motDePasse,
    ) {}
}
