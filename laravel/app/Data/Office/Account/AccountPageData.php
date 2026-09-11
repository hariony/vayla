<?php

namespace App\Data\Office\Account;

use Spatie\LaravelData\Data;

/** Les props de `Office/Account`. */
final class AccountPageData extends Data
{
    public function __construct(
        public readonly AccountData $compte,
    ) {}
}
