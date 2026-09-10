<?php

namespace App\Repositories;

use App\Enums\ListingStatus;
use App\Models\Destination;
use App\Models\Listing;
use App\Repositories\Contracts\DestinationRepositoryInterface;
use Illuminate\Support\Collection;

class DestinationRepository implements DestinationRepositoryInterface
{
    public function all(): Collection
    {
        return Destination::query()->with('photo')->orderBy('id')->get();
    }

    public function findBySlug(string $slug): ?Destination
    {
        return Destination::query()->with('photo')->where('slug', $slug)->first();
    }

    public function countListings(bool $includeDemo): array
    {
        $rows = Listing::query()
            ->join('destinations', 'destinations.id', '=', 'listings.destination_id')
            ->where('listings.status', ListingStatus::Published->value)
            ->when(! $includeDemo, fn ($q) => $q->where('listings.is_demo', false))
            ->groupBy('destinations.slug')
            ->selectRaw('destinations.slug as slug, count(*) as total')
            ->pluck('total', 'slug');

        return $rows->map(fn ($n) => (int) $n)->all();
    }
}
