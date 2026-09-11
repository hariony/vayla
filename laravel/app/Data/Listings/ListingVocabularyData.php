<?php

namespace App\Data\Listings;

use App\Data\OptionData;
use Spatie\LaravelData\Data;

/**
 * Ce que le formulaire d'annonce propose : les destinations — **choisies dans
 * une liste**, pas tapées —, les types de logement, les équipements par
 * rubrique.
 */
final class ListingVocabularyData extends Data
{
    /**
     * @param  list<DestinationChoiceData>  $destinations
     * @param  list<OptionData>  $kinds
     * @param  list<AmenityChoiceGroupData>  $amenityGroups
     */
    public function __construct(
        public readonly array $destinations,
        public readonly array $kinds,
        public readonly array $amenityGroups,
    ) {}

    /** @return array<string, mixed> les champs, pour qu'une page les reprenne à plat */
    public function champs(): array
    {
        return ['destinations' => $this->destinations, 'kinds' => $this->kinds, 'amenityGroups' => $this->amenityGroups];
    }
}
