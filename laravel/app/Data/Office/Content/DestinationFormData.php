<?php

namespace App\Data\Office\Content;

use App\Models\Destination;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

/**
 * Une destination, pour son formulaire. Les champs du formulaire gardent le
 * nom de leur colonne (`airport_code`…) : c'est ce que le formulaire renvoie.
 */
final class DestinationFormData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $publicUrl,
        public readonly int $listings,
        public readonly string $name,
        public readonly string $region,
        public readonly ?string $tagline,
        public readonly ?string $scene,
        #[MapOutputName('climate_zone')]
        public readonly ?string $climateZone,
        public readonly bool $featured,
        #[MapOutputName('airport_code')]
        public readonly ?string $airportCode,
        #[MapOutputName('airport_name')]
        public readonly ?string $airportName,
        #[MapOutputName('flight_from_tana')]
        public readonly ?string $flightFromTana,
        #[MapOutputName('road_route')]
        public readonly ?string $roadRoute,
        #[MapOutputName('road_km')]
        public readonly ?int $roadKm,
        #[MapOutputName('road_hours')]
        public readonly ?string $roadHours,
        #[MapOutputName('road_note')]
        public readonly ?string $roadNote,
    ) {}

    public static function fromModel(Destination $d, int $annonces): self
    {
        return new self(
            id: $d->id,
            slug: $d->slug,
            publicUrl: rtrim((string) config('app.url'), '/').'/destinations/'.$d->slug,
            listings: $annonces,
            name: $d->name,
            region: $d->region,
            tagline: $d->tagline,
            scene: $d->scene,
            climateZone: $d->climate_zone?->value,
            featured: (bool) $d->featured,
            airportCode: $d->airport_code,
            airportName: $d->airport_name,
            flightFromTana: $d->flight_from_tana,
            roadRoute: $d->road_route,
            roadKm: $d->road_km,
            roadHours: $d->road_hours,
            roadNote: $d->road_note,
        );
    }
}
