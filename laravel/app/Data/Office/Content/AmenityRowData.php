<?php

namespace App\Data\Office\Content;

use App\Models\Amenity;
use Spatie\LaravelData\Data;

/** Un équipement du vocabulaire, et combien de logements le déclarent. */
final class AmenityRowData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $key,
        public readonly string $label,
        public readonly ?string $icon,
        public readonly string $group,
        public readonly bool $filterable,
        public readonly int $listings,
    ) {}

    public static function fromModel(Amenity $a): self
    {
        return new self($a->id, $a->key, $a->label, $a->icon, $a->group->value, (bool) $a->filterable, (int) $a->listings_count);
    }
}
