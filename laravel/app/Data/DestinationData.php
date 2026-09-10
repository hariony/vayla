<?php

namespace App\Data;

use App\Models\Destination;
use Spatie\LaravelData\Data;

/**
 * `listings` est un compteur calculé, jamais saisi : aucun chiffre affiché
 * sur la page ne doit pouvoir mentir. Il est passé par le service, qui seul
 * sait si les annonces de démonstration comptent.
 */
class DestinationData extends Data
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $region,
        public readonly string $tagline,
        public readonly string $scene,
        public readonly ?string $photo,
        public readonly bool $featured,
        public readonly int $listings,
    ) {}

    public static function fromModel(Destination $destination, int $listings = 0): self
    {
        return new self(
            slug: $destination->slug,
            name: $destination->name,
            region: $destination->region,
            tagline: $destination->tagline,
            scene: $destination->scene,
            photo: $destination->photo?->key,
            featured: $destination->featured,
            listings: $listings,
        );
    }
}
