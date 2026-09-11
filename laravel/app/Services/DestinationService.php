<?php

namespace App\Services;

use App\Contracts\Repositories\DestinationRepositoryInterface;
use App\Data\DestinationData;
use App\Exceptions\DestinationNotFoundException;
use App\Services\Support\DemoMode;

class DestinationService
{
    public function __construct(
        private DestinationRepositoryInterface $repository,
        private DemoMode $demo,
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
    public function atlas(): array
    {
        $counts = $this->repository->countListings($this->demo->actif());

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

    public function show(string $slug): DestinationData
    {
        $destination = $this->repository->findBySlug($slug)
            ?? throw new DestinationNotFoundException($slug);

        $counts = $this->repository->countListings($this->demo->actif());

        return DestinationData::fromModel($destination, $counts[$slug] ?? 0);
    }
}
