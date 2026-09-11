<?php

namespace App\Repositories;

use App\Contracts\Repositories\PhotoRepositoryInterface;
use App\Models\Photo;
use Illuminate\Support\Collection;

class PhotoRepository implements PhotoRepositoryInterface
{
    public function all(): Collection
    {
        return Photo::query()->orderBy('id')->get();
    }

    public function credited(): Collection
    {
        return Photo::query()
            ->where('folder', '!=', 'annonces')
            ->where(fn ($q) => $q->where('folder', '!=', 'destinations')
                ->orWhereExists(fn ($e) => $e->selectRaw('1')->from('destination_photo')->whereColumn('destination_photo.photo_id', 'photos.id'))
                ->orWhereExists(fn ($e) => $e->selectRaw('1')->from('listing_photo')->whereColumn('listing_photo.photo_id', 'photos.id')))
            ->orderBy('id')
            ->get();
    }

    public function findByKey(string $key): ?Photo
    {
        return Photo::query()->where('key', $key)->first();
    }
}
