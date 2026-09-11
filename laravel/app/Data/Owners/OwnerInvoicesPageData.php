<?php

namespace App\Data\Owners;

use App\Data\Invoices\InvoiceHistoryData;
use Spatie\LaravelData\Data;

/** Les props de `Owner/Invoices` : le mois en cours, l'historique, et le taux des nouvelles demandes. */
final class OwnerInvoicesPageData extends Data
{
    public function __construct(
        public readonly InvoiceHistoryData $facturation,
        public readonly float $taux,
    ) {}
}
