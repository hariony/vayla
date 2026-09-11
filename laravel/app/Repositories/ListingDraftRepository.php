<?php

namespace App\Repositories;

use App\Contracts\Repositories\ListingDraftRepositoryInterface;
use App\DTOs\Listings\AmenityChoiceDto;
use App\DTOs\Listings\ListingFicheDto;
use App\Enums\ListingStatus;
use App\Models\Amenity;
use App\Models\Destination;
use App\Models\Listing;
use App\Models\Owner;
use Illuminate\Support\Collection;

class ListingDraftRepository implements ListingDraftRepositoryInterface
{
    public function destinations(): Collection
    {
        return Destination::query()->orderBy('name')->get(['id', 'name', 'region']);
    }

    public function equipements(): Collection
    {
        return Amenity::query()->orderBy('position')->get();
    }

    public function slugPris(string $slug): bool
    {
        return Listing::query()->where('slug', $slug)->exists();
    }

    public function creer(Owner $owner, ListingFicheDto $fiche, string $slug): Listing
    {
        $listing = new Listing($fiche->attributs());
        $listing->owner_id = $owner->id;
        $listing->slug = $slug;
        $listing->status = ListingStatus::Draft;
        $listing->trust_level = 1;
        $listing->save();

        return $listing;
    }

    public function modifierFiche(Listing $listing, ListingFicheDto $fiche, ?array $seulement = null): void
    {
        $attributs = $fiche->attributs();

        $listing->fill($seulement === null ? $attributs : array_intersect_key($attributs, array_flip($seulement)))->save();
    }

    public function poserEquipements(Listing $listing, array $choix): void
    {
        $listing->amenities()->sync(
            collect($choix)->mapWithKeys(fn (AmenityChoiceDto $c) => [$c->id => ['highlight' => $c->highlight]])->all()
        );
    }

    public function nombrePhotos(Listing $listing): int
    {
        return $listing->photos()->count();
    }

    public function nombreEquipements(Listing $listing): int
    {
        return $listing->amenities()->count();
    }

    public function soumettre(Listing $listing): void
    {
        $listing->forceFill(['status' => ListingStatus::Submitted, 'review_note' => null])->save();
    }

    public function pourEdition(Listing $listing): Listing
    {
        return $listing->load(['amenities', 'photos']);
    }
}
