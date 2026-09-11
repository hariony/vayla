<?php

namespace App\Repositories;

use App\Contracts\Repositories\OfficeAmenityRepositoryInterface;
use App\DTOs\Content\AmenityDto;
use App\Enums\AmenityGroup;
use App\Models\Amenity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OfficeAmenityRepository implements OfficeAmenityRepositoryInterface
{
    public function tous(): Collection
    {
        return Amenity::query()->withCount('listings')->orderBy('position')->orderBy('id')->get();
    }

    public function icones(): array
    {
        return Amenity::query()->distinct()->orderBy('icon')->pluck('icon')->push('dot')->unique()->values()->all();
    }

    public function total(): int
    {
        return Amenity::query()->count();
    }

    public function cleExiste(string $cle): bool
    {
        return Amenity::query()->where('key', $cle)->exists();
    }

    public function positionSuivante(AmenityGroup $rubrique): int
    {
        return (int) Amenity::query()->where('group', $rubrique->value)->max('position') + 1;
    }

    public function ordre(AmenityGroup $rubrique): array
    {
        return Amenity::query()->where('group', $rubrique->value)->orderBy('position')->orderBy('id')
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function creer(string $cle, int $position, AmenityDto $equipement): Amenity
    {
        return Amenity::create([
            'key' => $cle,
            'position' => $position,
            'label' => $equipement->label,
            'group' => $equipement->group,
            'icon' => $equipement->icon,
            'filterable' => $equipement->filterable,
        ]);
    }

    public function modifier(Amenity $equipement, AmenityDto $donnees): void
    {
        $equipement->fill([
            'label' => $donnees->label,
            'group' => $donnees->group,
            'icon' => $donnees->icon,
            'filterable' => $donnees->filterable,
        ])->save();
    }

    public function ordonner(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $position => $id) {
                Amenity::query()->whereKey($id)->update(['position' => $position]);
            }
        });
    }

    public function supprimer(Amenity $equipement): void
    {
        $equipement->delete();
    }

    public function nombreAnnonces(Amenity $equipement): int
    {
        return $equipement->listings()->count();
    }
}
