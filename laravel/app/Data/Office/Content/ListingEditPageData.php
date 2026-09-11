<?php

namespace App\Data\Office\Content;

use App\Data\Office\OwnerRefData;
use Spatie\LaravelData\Data;

/**
 * Les props de `Office/Listings/Edit`. `annonce` et `vocabulaire` reprennent la
 * forme de `OwnerListingService` — le même formulaire que celui du
 * propriétaire — et deviendront typés avec lui, au lot 3.
 */
final class ListingEditPageData extends Data
{
    /**
     * @param  array<string, mixed>|null  $annonce
     * @param  array<string, mixed>  $vocabulaire
     */
    public function __construct(
        public readonly ?array $annonce,
        public readonly ?OwnerRefData $proprietaire,
        public readonly array $vocabulaire,
    ) {}
}
