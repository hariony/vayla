<?php

namespace App\Data\Api;

use App\Data\ListingData;
use Spatie\LaravelData\Data;

/** La réponse de `/api/v1/listings` : une page d'annonces, et où l'on en est. */
final class ListingIndexData extends Data
{
    /** @param  list<ListingData>  $data */
    public function __construct(
        public readonly array $data,
        public readonly ListingIndexMetaData $meta,
    ) {}
}
