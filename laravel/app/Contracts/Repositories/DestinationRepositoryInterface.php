<?php

namespace App\Contracts\Repositories;

use App\Models\Destination;
use Illuminate\Support\Collection;

interface DestinationRepositoryInterface
{
    /** @return Collection<int, Destination> */
    public function all(): Collection;

    public function findBySlug(string $slug): ?Destination;

    /** Avec sa galerie, dans l'ordre choisi au back-office : la page publique d'une destination. */
    public function findWithGallery(string $slug): ?Destination;

    public function findById(int $id): ?Destination;

    /**
     * Nombre d'annonces publiées par slug de destination.
     *
     * @return array<string, int>
     */
    public function countListings(bool $includeDemo): array;
}
