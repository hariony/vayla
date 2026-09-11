<?php

namespace App\DTOs\Office;

/** Un nouveau membre de l'équipe : son nom, son adresse — normalisée en minuscules. */
final readonly class NewMemberDto
{
    public function __construct(
        public string $nom,
        public string $email,
    ) {}
}
