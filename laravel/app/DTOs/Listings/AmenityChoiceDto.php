<?php

namespace App\DTOs\Listings;

/** Un équipement coché sur une fiche, et s'il fait partie des arguments mis en avant. */
final readonly class AmenityChoiceDto
{
    public function __construct(
        public int $id,
        public bool $highlight = false,
    ) {}
}
