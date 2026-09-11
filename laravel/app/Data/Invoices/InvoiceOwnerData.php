<?php

namespace App\Data\Invoices;

use App\Models\Owner;
use Spatie\LaravelData\Data;

/**
 * Qui doit, et vers quel numéro il pousse son règlement. **Rien qui permette
 * de le débiter** : Vayla n'encaisse pas, le propriétaire règle par mobile
 * money.
 */
final class InvoiceOwnerData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $phone,
        public readonly ?string $mobileMoney,
        public readonly ?string $operator,
    ) {}

    public static function fromModel(Owner $owner): self
    {
        return new self($owner->name, $owner->phone, $owner->mobile_money, $owner->mobile_money_operator);
    }
}
