<?php

namespace App\Data\Office\Invoices;

use Spatie\LaravelData\Data;

/** Un règlement consigné : quand, quelle référence mobile money, combien. */
final class SettlementData extends Data
{
    public function __construct(
        public readonly string $at,
        public readonly ?string $reference,
        public readonly int $amount,
    ) {}
}
