<?php

namespace App\Data\Office\Invoices;

use Spatie\LaravelData\Data;

/** Les props de `Office/Invoices/Index`. */
final class InvoicesPageData extends Data
{
    public function __construct(
        public readonly InvoiceMonthData $mois,
        public readonly array $lignes,
        public readonly InvoiceTotalsData $totaux,
        public readonly float $taux,
    ) {}
}
