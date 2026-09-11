<?php

namespace App\Data\Pages;

use App\Data\AmenityGroupData;
use App\Data\CategoryData;
use App\Data\DestinationData;
use App\Data\KeyLabelData;
use App\Data\ListingData;
use App\Data\PhotoData;
use App\Data\TrustLevelData;
use Spatie\LaravelData\Data;

/**
 * Les props de `Listings/Index`. Les visites de filtre sont partielles
 * (`only: ['listings', 'meta', 'filtre']`) : le reste ne change pas entre deux
 * clics.
 */
final class CataloguePageData extends Data
{
    /**
     * @param  list<ListingData>  $listings
     * @param  list<AmenityGroupData>  $amenityFilters
     * @param  list<KeyLabelData>  $sorts
     * @param  list<DestinationData>  $destinations
     * @param  list<CategoryData>  $categories
     * @param  list<TrustLevelData>  $trustLevels
     * @param  array<string, PhotoData>  $photos
     * @param  list<PhotoData>  $credits
     */
    public function __construct(
        public readonly array $listings,
        public readonly CatalogueMetaData $meta,
        public readonly CatalogueFilterData $filtre,
        public readonly CatalogueFacetsData $facets,
        public readonly array $amenityFilters,
        public readonly array $sorts,
        public readonly array $destinations,
        public readonly array $categories,
        public readonly array $trustLevels,
        public readonly bool $demo,
        public readonly array $photos,
        public readonly array $credits,
    ) {}
}
