<?php

namespace App\Data\Office\Listings;

use App\Data\Office\OwnerCardData;
use Spatie\LaravelData\Data;

/** Les props de `Office/Listings/Show`. */
final class ListingModerationPageData extends Data
{
    public function __construct(
        public readonly ListingDetailData $annonce,
        public readonly ?OwnerCardData $proprietaire,
        public readonly array $niveaux,
        public readonly PublicationData $publication,
        public readonly array $journal,
    ) {}
}
