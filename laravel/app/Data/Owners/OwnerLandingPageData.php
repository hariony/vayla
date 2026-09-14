<?php

namespace App\Data\Owners;

use App\Data\PhotoData;
use App\Data\TrustLevelData;
use Spatie\LaravelData\Data;

/**
 * La page `/louer-mon-logement` : deux vraies photographies de Madagascar, avec
 * leurs crédits. Nulles si elles ont disparu de la photothèque — la page tient
 * sans elles, elle ne tombe pas pour une image.
 */
final class OwnerLandingPageData extends Data
{
    public function __construct(
        /** Le lieu, en fond de la fiche : le lac Itasy, à Ampefy. */
        public readonly ?PhotoData $lieu,
        /** La photo de la fiche d'exemple : une villa avec piscine, à Ampefy. */
        public readonly ?PhotoData $fiche,
        /**
         * Les trois premiers barreaux, que la fiche d'exemple franchit. Leurs
         * libellés viennent de `TrustLevel` : la page doit dire exactement ce
         * que disent les fiches.
         *
         * @var list<TrustLevelData>
         */
        public readonly array $niveaux,
    ) {}
}
