<?php

namespace App\Data\Office\Content;

use App\Data\Office\PhotoRefData;
use App\Models\Destination;
use Spatie\LaravelData\Data;

/** Une ligne de la liste des destinations. `acces` dit si le trajet depuis Tana est renseigné. */
final class DestinationRowData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly string $region,
        public readonly ?string $tagline,
        public readonly ?string $zone,
        public readonly bool $featured,
        public readonly int $listings,
        public readonly ?PhotoRefData $photo,
        public readonly bool $acces,
    ) {}

    public static function fromModel(Destination $d): self
    {
        return new self(
            id: $d->id,
            slug: $d->slug,
            name: $d->name,
            region: $d->region,
            tagline: $d->tagline,
            zone: $d->climate_zone?->label(),
            featured: (bool) $d->featured,
            listings: (int) $d->listings_count,
            photo: $d->photo ? PhotoRefData::fromModel($d->photo) : null,
            acces: (bool) ($d->airport_code || $d->road_route || $d->road_note),
        );
    }
}
