<?php

namespace App\Repositories;

use App\Contracts\Repositories\OfficeListingContentRepositoryInterface;
use App\DTOs\Listings\ListingFicheDto;
use App\Models\Amenity;
use App\Models\Listing;
use App\Models\Photo;

class OfficeListingContentRepository implements OfficeListingContentRepositoryInterface
{
    public function modifierFiche(Listing $listing, ListingFicheDto $fiche): array
    {
        $listing->fill($fiche->attributs())->save();

        return array_keys(array_diff_key($listing->getChanges(), ['updated_at' => true]));
    }

    public function empreinteEquipements(Listing $listing): string
    {
        return $listing->amenities()->get()
            ->map(fn (Amenity $a) => $a->id.':'.(int) $a->pivot->highlight)
            ->sort()
            ->implode(',');
    }

    public function poserCategories(Listing $listing, array $ids): bool
    {
        $resultat = $listing->categories()->sync($ids);

        return $resultat['attached'] !== [] || $resultat['detached'] !== [];
    }

    public function categories(Listing $listing): array
    {
        return $listing->categories()->pluck('categories.id')->map(fn ($id) => (int) $id)->all();
    }

    public function photoDeLaGalerie(Listing $listing, int $photoId): ?Photo
    {
        return $listing->photos()->where('photos.id', $photoId)->first();
    }

    public function charger(Listing $listing): Listing
    {
        return $listing->load(['amenities', 'photos', 'categories', 'owner']);
    }
}
