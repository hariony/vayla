<?php

namespace App\Data\Office\Owners;

use App\Data\Office\Invoices\InvoiceSummaryData;
use Spatie\LaravelData\Data;

/** Les factures d'un propriétaire : le mois qui s'accumule, puis les passées. */
final class OwnerInvoicesData extends Data
{
    public function __construct(
        public readonly InvoiceSummaryData $encours,
        public readonly array $passees,
    ) {}
}
