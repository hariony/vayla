<?php

namespace App\Data\Office\Invoices;

use App\Data\Invoices\InvoiceOwnerData;
use Spatie\LaravelData\Data;

/** Une facture de propriétaire, et son règlement s'il est consigné. `owner` et `lines` gardent la forme de `InvoiceService` (lot 3). */
final class InvoiceSummaryData extends Data
{
    public function __construct(
        public readonly string $mois,
        public readonly string $label,
        public readonly InvoiceOwnerData $owner,
        public readonly array $lines,
        public readonly int $stays,
        public readonly int $nights,
        public readonly int $revenue,
        public readonly int $due,
        public readonly ?SettlementData $settlement,
        public readonly ?int $ownerId = null,
    ) {}
}
