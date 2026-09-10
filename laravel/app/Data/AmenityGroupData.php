<?php

namespace App\Data;

use App\Enums\AmenityGroup;
use Spatie\LaravelData\Data;

/**
 * Une rubrique d'équipements et ce qu'elle contient.
 *
 * C'est la forme que consomment la fiche d'annonce et le panneau de filtres :
 * les deux affichent des équipements groupés, il n'y a donc qu'un objet.
 */
class AmenityGroupData extends Data
{
    /** @param array<int, AmenityData> $amenities */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly ?string $note,
        public readonly array $amenities,
    ) {}

    /** @param array<int, AmenityData> $amenities */
    public static function fromEnum(AmenityGroup $group, array $amenities): self
    {
        return new self(
            key: $group->value,
            label: $group->label(),
            note: $group->note(),
            amenities: array_values($amenities),
        );
    }
}
