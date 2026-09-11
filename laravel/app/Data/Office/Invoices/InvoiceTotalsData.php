<?php

namespace App\Data\Office\Invoices;

use Spatie\LaravelData\Data;

/** Les totaux d'un mois de facturation. */
final class InvoiceTotalsData extends Data
{
    public function __construct(
        public readonly int $du,
        public readonly int $regle,
        public readonly int $reste,
        public readonly int $sejours,
        public readonly int $revenus,
    ) {}
}
