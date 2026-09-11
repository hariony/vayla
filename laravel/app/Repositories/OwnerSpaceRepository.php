<?php

namespace App\Repositories;

use App\Contracts\Repositories\OwnerSpaceRepositoryInterface;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Owner;
use Illuminate\Support\Collection;

class OwnerSpaceRepository implements OwnerSpaceRepositoryInterface
{
    public function reservations(Owner $owner): Collection
    {
        return $owner->bookings()->with(['listing', 'messages'])->get();
    }

    public function reservation(Owner $owner, string $reference): ?Booking
    {
        return Booking::query()
            ->with(['listing', 'messages'])
            ->where('reference', $reference)
            ->whereHas('listing', fn ($q) => $q->where('owner_id', $owner->id))
            ->first();
    }

    public function annonces(Owner $owner): Collection
    {
        return $owner->listings()->with(['destination', 'photos', 'unavailabilities'])->get();
    }

    public function annonce(Owner $owner, string $slug): ?Listing
    {
        return $owner->listings()->with(['destination', 'photos', 'unavailabilities', 'bookings'])->where('slug', $slug)->first();
    }
}
