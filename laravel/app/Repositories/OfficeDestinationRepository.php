<?php

namespace App\Repositories;

use App\Contracts\Repositories\OfficeDestinationRepositoryInterface;
use App\DTOs\Content\DestinationDto;
use App\Models\Destination;
use Illuminate\Support\Collection;

class OfficeDestinationRepository implements OfficeDestinationRepositoryInterface
{
    public function toutes(): Collection
    {
        return Destination::query()->with('photo')->withCount('listings')->orderBy('name')->get();
    }

    public function nombreAnnonces(Destination $destination): int
    {
        return $destination->listings()->count();
    }

    public function slugExiste(string $slug): bool
    {
        return Destination::query()->where('slug', $slug)->exists();
    }

    public function creer(string $slug, DestinationDto $destination): Destination
    {
        return Destination::create(['slug' => $slug, ...$this->attributs($destination)]);
    }

    public function modifier(Destination $destination, DestinationDto $donnees): void
    {
        $destination->fill($this->attributs($donnees))->save();
    }

    public function supprimer(Destination $destination): void
    {
        $destination->delete();
    }

    /** @return array<string, mixed> */
    private function attributs(DestinationDto $d): array
    {
        return [
            'name' => $d->name,
            'region' => $d->region,
            'tagline' => $d->tagline,
            'climate_zone' => $d->climateZone,
            'scene' => $d->scene->value,
            'featured' => $d->featured,
            'airport_code' => $d->airportCode,
            'airport_name' => $d->airportName,
            'flight_from_tana' => $d->flightFromTana,
            'road_route' => $d->roadRoute,
            'road_km' => $d->roadKm,
            'road_hours' => $d->roadHours,
            'road_note' => $d->roadNote,
        ];
    }
}
