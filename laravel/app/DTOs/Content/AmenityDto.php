<?php

namespace App\DTOs\Content;

use App\Enums\AmenityGroup;

/** Un équipement du vocabulaire. Sa clé ne se saisit pas : elle naît du libellé. */
final readonly class AmenityDto
{
    public function __construct(
        public string $label,
        public AmenityGroup $group,
        public string $icon,
        public bool $filterable,
    ) {}
}
