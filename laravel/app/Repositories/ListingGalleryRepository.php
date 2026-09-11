<?php

namespace App\Repositories;

use App\Contracts\Repositories\ListingGalleryRepositoryInterface;
use App\Models\Listing;
use App\Models\Photo;

class ListingGalleryRepository implements ListingGalleryRepositoryInterface
{
    public function creerPhoto(string $cle, int $largeur, string $legende): Photo
    {
        return Photo::create([
            'key' => $cle,
            'folder' => 'annonces',
            'width' => $largeur,
            'is_ai' => false,
            'caption' => $legende,
        ]);
    }

    public function accrocher(Listing $listing, Photo $photo): void
    {
        $listing->photos()->attach($photo->id, ['position' => (int) $listing->photos()->max('position') + 1]);
    }

    public function decrocher(Listing $listing, Photo $photo): void
    {
        $listing->photos()->detach($photo->id);
    }

    public function positionner(Listing $listing, int $photoId, int $position): void
    {
        $listing->photos()->updateExistingPivot($photoId, ['position' => $position]);
    }

    public function ids(Listing $listing): array
    {
        return $listing->photos()->pluck('photos.id')->map(fn ($id) => (int) $id)->all();
    }

    public function photo(Listing $listing, int $photoId): ?Photo
    {
        return $listing->photos()->where('photos.id', $photoId)->first();
    }

    public function supprimer(Photo $photo): void
    {
        $photo->delete();
    }
}
