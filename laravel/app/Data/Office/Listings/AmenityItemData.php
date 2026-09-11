<?php

namespace App\Data\Office\Listings;

use Spatie\LaravelData\Data;

/** Un équipement déclaré, s'il est mis en avant, et la précision du propriétaire. */
final class AmenityItemData extends Data
{
    public function __construct(
        public readonly string $label,
        public readonly bool $highlight,
        public readonly ?string $note,
    ) {}
}
