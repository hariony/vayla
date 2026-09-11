<?php

namespace App\DTOs\Office;

use App\Enums\ListingStatus;

/** La file des annonces : un statut (ou toutes), une recherche. */
final readonly class ListingQueueFilterDto
{
    public function __construct(
        public ?ListingStatus $statut = null,
        public ?string $recherche = null,
    ) {}
}
