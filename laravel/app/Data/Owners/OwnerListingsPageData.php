<?php

namespace App\Data\Owners;

use Spatie\LaravelData\Data;

/** Les props de `Owner/Listings/Index`. */
final class OwnerListingsPageData extends Data
{
    /** @param  list<OwnerListingRowData>  $listings */
    public function __construct(
        public readonly array $listings,
    ) {}
}
