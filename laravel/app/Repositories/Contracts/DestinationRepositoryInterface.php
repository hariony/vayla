<?php

namespace App\Repositories\Contracts;

use App\Models\Destination;
use Illuminate\Support\Collection;

interface DestinationRepositoryInterface
{
    /** @return Collection<int, Destination> */
    public function all(): Collection;

    public function findBySlug(string $slug): ?Destination;

    /**
     * Nombre d'annonces publiées par slug de destination.
     *
     * @return array<string, int>
     */
    public function countListings(bool $includeDemo): array;
}
