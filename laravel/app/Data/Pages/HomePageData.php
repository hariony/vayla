<?php

namespace App\Data\Pages;

use App\Data\CategoryData;
use App\Data\DestinationData;
use App\Data\ListingData;
use App\Data\PhotoData;
use App\Data\TrustLevelData;
use Spatie\LaravelData\Data;

/**
 * Les props de `Home/Index`. `demo` tient deux choses ensemble : les annonces
 * fictives servies **et** le bandeau « Aperçu » qui le dit à l'écran.
 */
final class HomePageData extends Data
{
    /**
     * @param  list<DestinationData>  $destinations
     * @param  list<TrustLevelData>  $trustLevels
     * @param  list<CategoryData>  $categories
     * @param  list<ListingData>  $listings
     * @param  array<string, PhotoData>  $photos  par clé
     * @param  list<PhotoData>  $credits
     */
    public function __construct(
        public readonly array $destinations,
        public readonly array $trustLevels,
        public readonly array $categories,
        public readonly array $listings,
        public readonly bool $demo,
        public readonly array $photos,
        public readonly array $credits,
    ) {}
}
