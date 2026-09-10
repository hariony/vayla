<?php

namespace App\Repositories\Contracts;

use App\Models\Amenity;
use Illuminate\Support\Collection;

interface AmenityRepositoryInterface
{
    /** @return Collection<int, Amenity> */
    public function all(): Collection;

    /** Le sous-ensemble qui a droit à une case dans la recherche. */
    public function filterable(): Collection;

    /** @param  array<int, string>  $keys */
    public function findByKeys(array $keys): Collection;
}
