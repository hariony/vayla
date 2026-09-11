<?php

namespace App\Data\Pages;

use App\Data\DestinationData;
use App\Data\PhotoData;
use Spatie\LaravelData\Data;

/** Les props de `Destinations/Index` : l'atlas, destinations vides comprises. */
final class AtlasPageData extends Data
{
    /**
     * @param  list<DestinationData>  $destinations
     * @param  array<string, PhotoData>  $photos
     * @param  list<PhotoData>  $credits
     */
    public function __construct(
        public readonly array $destinations,
        public readonly bool $demo,
        public readonly array $photos,
        public readonly array $credits,
    ) {}
}
