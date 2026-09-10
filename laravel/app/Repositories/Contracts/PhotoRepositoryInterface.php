<?php

namespace App\Repositories\Contracts;

use App\Models\Photo;
use Illuminate\Support\Collection;

interface PhotoRepositoryInterface
{
    /** @return Collection<int, Photo> */
    public function all(): Collection;

    public function findByKey(string $key): ?Photo;
}
