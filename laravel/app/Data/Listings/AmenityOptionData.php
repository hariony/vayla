<?php

namespace App\Data\Listings;

use App\Models\Amenity;
use Spatie\LaravelData\Data;

/** Une case à cocher du formulaire d'annonce. */
final class AmenityOptionData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $key,
        public readonly string $label,
        public readonly ?string $icon,
    ) {}

    public static function fromModel(Amenity $a): self
    {
        return new self($a->id, $a->key, $a->label, $a->icon);
    }
}
