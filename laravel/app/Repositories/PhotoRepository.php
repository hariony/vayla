<?php

namespace App\Repositories;

use App\Models\Photo;
use App\Repositories\Contracts\PhotoRepositoryInterface;
use Illuminate\Support\Collection;

class PhotoRepository implements PhotoRepositoryInterface
{
    public function all(): Collection
    {
        return Photo::query()->orderBy('id')->get();
    }

    public function findByKey(string $key): ?Photo
    {
        return Photo::query()->where('key', $key)->first();
    }
}
