<?php

namespace App\Repositories;

use App\Contracts\Repositories\AmenityRepositoryInterface;
use App\Models\Amenity;
use Illuminate\Support\Collection;

class AmenityRepository implements AmenityRepositoryInterface
{
    public function all(): Collection
    {
        return Amenity::query()->orderBy('position')->get();
    }

    public function filterable(): Collection
    {
        return Amenity::query()->where('filterable', true)->orderBy('position')->get();
    }

    public function findByKeys(array $keys): Collection
    {
        if ($keys === []) {
            return new Collection;
        }

        return Amenity::query()->whereIn('key', $keys)->orderBy('position')->get();
    }
}
