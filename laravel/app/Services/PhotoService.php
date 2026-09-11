<?php

namespace App\Services;

use App\Contracts\Repositories\PhotoRepositoryInterface;
use App\Data\PhotoData;

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

    /** @return array<string, PhotoData> par clé */
    public function map(): array
    {
        return $this->repository->all()
            ->mapWithKeys(fn ($p) => [$p->key => PhotoData::fromModel($p)])
            ->all();
    }

    /**
     * Les photos à créditer : celles des lieux (Commons) et celles que
     * l'équipe a téléversées pour une destination. **Pas les photos des
     * propriétaires** : elles sont à eux, sans auteur ni licence à citer, et
     * les lister sous « Crédits photo » les ferait passer pour des
     * photographies de Commons.
     *
     * @return array<int, PhotoData>
     */
    public function credits(): array
    {
        // Ni les photos des propriétaires, qui sont à eux, ni une photo de
        // l'équipe qui n'illustre encore rien : on ne crédite pas une image
        // qu'on ne publie pas.
        return $this->repository->credited()
            ->map(fn ($p) => PhotoData::fromModel($p))
            ->values()
            ->all();
    }
}
