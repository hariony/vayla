<?php

namespace App\DTOs\Auth;

/**
 * Le compte voyageur : nom, prénom, téléphone — **et chacun sert deux fois** :
 * le propriétaire les lit avant d'accepter quelqu'un chez lui, et les trois
 * pré-remplissent la demande de séjour. Tout est facultatif.
 */
final readonly class TravellerAccountDto
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?string $phone,
    ) {}
}
