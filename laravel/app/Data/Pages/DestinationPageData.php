<?php

namespace App\Data\Pages;

use App\Data\DestinationData;
use App\Data\DestinationDetailData;
use App\Data\PhotoData;
use Spatie\LaravelData\Data;

/**
 * Les props de `Destinations/Show`. `galerie` : les clés des photos, dans
 * l'ordre choisi au back-office — la première est la couverture, celle de
 * l'atlas.
 */
final class DestinationPageData extends Data
{
    /**
     * @param  list<DestinationData>  $destinations
     * @param  list<string>  $galerie
     * @param  array<string, PhotoData>  $photos
     * @param  list<PhotoData>  $credits
     */
    public function __construct(
        public readonly DestinationDetailData $fiche,
        public readonly array $destinations,
        public readonly bool $demo,
        public readonly array $galerie,
        public readonly array $photos,
        public readonly array $credits,
    ) {}
}
