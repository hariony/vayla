<?php

namespace App\Data;

use App\Models\Amenity;
use Spatie\LaravelData\Data;

/**
 * Un équipement, tel que le front et l'application le reçoivent.
 *
 * `highlight` et `note` viennent du pivot : ils n'ont de sens que rapportés
 * à un logement précis. Servi hors annonce (la liste des filtres), l'objet
 * les laisse à leur valeur neutre.
 */
class AmenityData extends Data
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $group,
        public readonly string $icon,
        public readonly bool $filterable = false,
        public readonly bool $highlight = false,
        public readonly ?string $note = null,
    ) {}

    /** Le vocabulaire seul, sans logement de rattachement. */
    public static function fromModel(Amenity $amenity): self
    {
        return new self(
            key: $amenity->key,
            label: $amenity->label,
            group: $amenity->group->value,
            icon: $amenity->icon,
            filterable: $amenity->filterable,
        );
    }

    /** L'équipement tel qu'il est attaché à une annonce. */
    public static function fromPivot(Amenity $amenity): self
    {
        return new self(
            key: $amenity->key,
            label: $amenity->label,
            group: $amenity->group->value,
            icon: $amenity->icon,
            filterable: $amenity->filterable,
            highlight: (bool) $amenity->pivot->highlight,
            note: $amenity->pivot->note,
        );
    }
}
