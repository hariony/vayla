<?php

namespace App\Data\Owners;

use App\Models\Owner;
use Spatie\LaravelData\Data;

/** Le propriétaire, en tête de son tableau de bord : qui il est, et vers quel numéro il règle. */
final class OwnerSummaryData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $city,
        public readonly ?string $phone,
        public readonly ?string $mobileMoney,
        public readonly ?string $operator,
    ) {}

    public static function fromModel(Owner $o): self
    {
        return new self($o->name, $o->city, $o->phone, $o->mobile_money, $o->mobile_money_operator);
    }
}
