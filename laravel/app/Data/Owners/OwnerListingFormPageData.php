<?php

namespace App\Data\Owners;

use App\Data\Listings\AmenityChoiceGroupData;
use App\Data\Listings\DestinationChoiceData;
use App\Data\Listings\ListingFormData;
use App\Data\Listings\ListingVocabularyData;
use App\Data\OptionData;
use Spatie\LaravelData\Data;

/** Les props de `Owner/Listings/Form` : l'annonce (nulle à la création), et le vocabulaire à plat. */
final class OwnerListingFormPageData extends Data
{
    /**
     * @param  list<DestinationChoiceData>  $destinations
     * @param  list<OptionData>  $kinds
     * @param  list<AmenityChoiceGroupData>  $amenityGroups
     */
    public function __construct(
        public readonly ?ListingFormData $listing,
        public readonly array $destinations,
        public readonly array $kinds,
        public readonly array $amenityGroups,
    ) {}

    public static function depuis(?ListingFormData $listing, ListingVocabularyData $vocabulaire): self
    {
        return new self(...['listing' => $listing, ...$vocabulaire->champs()]);
    }
}
