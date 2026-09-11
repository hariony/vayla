<?php

namespace App\Data\Pages;

use App\Data\ListingData;
use App\Data\ListingDetailData;
use App\Data\PhotoData;
use App\Data\SejourData;
use App\Data\TrustLevelData;
use Spatie\LaravelData\Data;

/**
 * Les props de `Listings/Show`. `sejour` est **une suggestion, pas un
 * critère** : les dates venues du moteur, que le calendrier pré-sélectionne
 * s'il le peut.
 */
final class ListingPageData extends Data
{
    /**
     * @param  list<ListingData>  $similar
     * @param  list<TrustLevelData>  $trustLevels
     * @param  array<string, PhotoData>  $photos
     * @param  list<PhotoData>  $credits
     */
    public function __construct(
        public readonly ListingDetailData $fiche,
        public readonly ?SejourData $sejour,
        public readonly array $similar,
        public readonly array $trustLevels,
        public readonly bool $demo,
        public readonly array $photos,
        public readonly array $credits,
    ) {}
}
