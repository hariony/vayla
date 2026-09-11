<?php

namespace App\DTOs\Owners;

use App\Enums\MobileMoneyOperator;

/** « Mes informations » du propriétaire. Les numéros arrivent en E.164, normalisés par la requête. */
final readonly class OwnerAccountDto
{
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $city,
        public ?string $address,
        public ?string $mobileMoney,
        public ?MobileMoneyOperator $operator,
    ) {}
}
