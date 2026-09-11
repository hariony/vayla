<?php

namespace App\Data\Office\Content;

use App\Data\Listings\AmenityChoiceGroupData;
use App\Data\Listings\DestinationChoiceData;
use App\Data\Listings\ListingVocabularyData;
use App\Data\OptionData;
use Spatie\LaravelData\Data;

/** Le vocabulaire du formulaire d'annonce, plus les catégories éditoriales que seule l'équipe pose. */
final class OfficeListingVocabularyData extends Data
{
    /**
     * @param  list<DestinationChoiceData>  $destinations
     * @param  list<OptionData>  $kinds
     * @param  list<AmenityChoiceGroupData>  $amenityGroups
     * @param  list<EditorialCategoryData>  $categories
     */
    public function __construct(
        public readonly array $destinations,
        public readonly array $kinds,
        public readonly array $amenityGroups,
        public readonly array $categories,
    ) {}

    /** @param  list<EditorialCategoryData>  $categories */
    public static function depuis(ListingVocabularyData $vocabulaire, array $categories): self
    {
        return new self(...[...$vocabulaire->champs(), 'categories' => $categories]);
    }
}
