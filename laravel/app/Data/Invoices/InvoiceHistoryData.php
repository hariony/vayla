<?php

namespace App\Data\Invoices;

use Spatie\LaravelData\Data;

/**
 * Le mois en cours — **qui s'accumule et n'est pas une facture** — puis les
 * factures des mois précédents, sans les mois vides.
 */
final class InvoiceHistoryData extends Data
{
    /** @param  list<InvoiceData>  $factures  la plus récente en tête */
    public function __construct(
        public readonly InvoiceData $encours,
        public readonly array $factures,
        public readonly InvoiceOwnerData $owner,
    ) {}
}
