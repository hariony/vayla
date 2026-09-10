<?php

namespace App\Services;

use App\Data\DestinationData;
use App\Exceptions\DestinationNotFoundException;
use App\Repositories\Contracts\DestinationRepositoryInterface;

class DestinationService
{
    public function __construct(
        private DestinationRepositoryInterface $repository,
    ) {}

    /**
     * L'atlas montre la vedette d'abord, puis ce qui est réservable avant
     * une file de zéros. La carte, elle, ne dépend pas de cet ordre : elle
     * indexe par slug.
     *
     * Les compteurs sont calculés depuis les annonces — aucun chiffre affiché
     * sur la page n'est saisi à la main.
     *
     * @return array<int, DestinationData>
     */
    public function atlas(bool $includeDemo): array
    {
        $counts = $this->repository->countListings($includeDemo);

        $destinations = $this->repository->all()
            ->map(fn ($d) => DestinationData::fromModel($d, $counts[$d->slug] ?? 0))
            ->values()
            ->all();

        usort(
            $destinations,
            static fn (DestinationData $a, DestinationData $b) => [$b->featured, $b->listings] <=> [$a->featured, $a->listings]
        );

        return $destinations;
    }

    public function show(string $slug, bool $includeDemo): DestinationData
    {
        $destination = $this->repository->findBySlug($slug)
            ?? throw new DestinationNotFoundException($slug);

        $counts = $this->repository->countListings($includeDemo);

        return DestinationData::fromModel($destination, $counts[$slug] ?? 0);
    }
}
