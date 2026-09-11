<?php

namespace App\Data\Office\Content;

use App\Data\Office\OwnerRefData;
use Spatie\LaravelData\Data;

/**
 * Les props de `Office/Listings/Edit` : le même formulaire que celui du
 * propriétaire, plus ce que seule l'équipe touche. `annonce` est nulle quand
 * l'équipe saisit une annonce pour un propriétaire.
 */
final class ListingEditPageData extends Data
{
    public function __construct(
        public readonly ?OfficeListingFormData $annonce,
        public readonly ?OwnerRefData $proprietaire,
        public readonly OfficeListingVocabularyData $vocabulaire,
    ) {}
}
