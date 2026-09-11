<?php

namespace App\DTOs\StayRequests;

use App\Enums\StayRequestStatus;

/** Ce que regarde la file des demandes : un statut, une recherche. */
final readonly class StayRequestFilterDto
{
    public function __construct(
        public StayRequestStatus $statut = StayRequestStatus::New,
        public string $recherche = '',
    ) {}
}
