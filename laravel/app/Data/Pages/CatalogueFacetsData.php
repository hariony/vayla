<?php

namespace App\Data\Pages;

use App\Data\KeyLabelData;
use Spatie\LaravelData\Data;

/**
 * Les bornes du panneau de filtres, sur **tout** le catalogue : les types de
 * logement présents, la fourchette de prix, le nombre d'annonces.
 */
final class CatalogueFacetsData extends Data
{
    /** @param  list<KeyLabelData>  $kinds */
    public function __construct(
        public readonly array $kinds,
        public readonly int $priceMin,
        public readonly int $priceMax,
        public readonly int $catalogue,
    ) {}
}
