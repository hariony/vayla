<?php

namespace App\Data\Office;

use Spatie\LaravelData\Data;

/** Le propriétaire d'une annonce, dans une ligne de file : son nom, et si son numéro est vérifié. */
final class ListingOwnerData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly bool $verified,
    ) {}
}
