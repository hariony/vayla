<?php

namespace App\DTOs\Office;

/** Une tentative de connexion : l'adresse (déjà en minuscules), le mot de passe, la machine. */
final readonly class LoginDto
{
    public function __construct(
        public string $email,
        public string $motDePasse,
        public string $ip,
    ) {}
}
