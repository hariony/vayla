<?php

namespace App\Data\Travellers;

use Spatie\LaravelData\Data;

/** Les props de `Auth/Account`. */
final class TravellerAccountPageData extends Data
{
    public function __construct(
        public readonly TravellerAccountData $compte,
    ) {}
}
