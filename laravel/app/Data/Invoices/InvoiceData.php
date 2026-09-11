<?php

namespace App\Data\Invoices;

use Spatie\LaravelData\Data;

/**
 * La facture d'un propriétaire pour un mois : **les séjours effectués partis
 * ce mois-là**, jamais les réservations. `basis` est écrit sur la facture : c'est
 * ce qui permet de la vérifier ligne à ligne.
 */
final class InvoiceData extends Data
{
    /** @param  list<InvoiceLineData>  $lines */
    public function __construct(
        public readonly InvoiceOwnerData $owner,
        public readonly InvoicePeriodData $period,
        public readonly array $lines,
        public readonly int $stays,
        public readonly int $nights,
        public readonly int $revenue,
        public readonly int $due,
        public readonly string $basis,
    ) {}
}
