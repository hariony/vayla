<?php

namespace App\Data\Owners;

use Spatie\LaravelData\Data;

/** Les props de `Owner/Account`. */
final class OwnerAccountPageData extends Data
{
    /** @param  list<string>  $operateurs */
    public function __construct(
        public readonly OwnerAccountData $compte,
        public readonly array $operateurs,
    ) {}
}
