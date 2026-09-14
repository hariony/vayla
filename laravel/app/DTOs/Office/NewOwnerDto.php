<?php

namespace App\DTOs\Office;

/**
 * Un propriétaire que l'équipe inscrit elle-même, au téléphone : son nom, son
 * numéro WhatsApp en E.164, et ce qu'il a bien voulu donner d'autre.
 */
final readonly class NewOwnerDto
{
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $email,
        public ?string $city,
    ) {}
}
