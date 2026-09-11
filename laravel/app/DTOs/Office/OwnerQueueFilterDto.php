<?php

namespace App\DTOs\Office;

/** La file des propriétaires : tous, ou ceux dont le numéro reste à vérifier ; une recherche. */
final readonly class OwnerQueueFilterDto
{
    public function __construct(
        public bool $numeroAVerifier = false,
        public ?string $recherche = null,
    ) {}
}
