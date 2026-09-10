<?php

namespace App\Services;

use App\Data\PhotoData;
use App\Repositories\Contracts\PhotoRepositoryInterface;

/**
 * Les crédits photo ne sont pas décoratifs : CC BY et CC BY-SA les exigent.
 * Le service expose donc les deux formes dont la page a besoin — la table
 * indexée par clé, pour retrouver une légende, et la liste ordonnée du
 * bloc « Crédits photo » du pied de page.
 */
class PhotoService
{
    public function __construct(
        private PhotoRepositoryInterface $repository,
    ) {}

    /** @return array<string, array<string, mixed>> */
    public function map(): array
    {
        return $this->repository->all()
            ->mapWithKeys(fn ($p) => [$p->key => PhotoData::fromModel($p)->toArray()])
            ->all();
    }

    /** @return array<int, PhotoData> */
    public function credits(): array
    {
        return $this->repository->all()
            ->map(fn ($p) => PhotoData::fromModel($p))
            ->values()
            ->all();
    }
}
