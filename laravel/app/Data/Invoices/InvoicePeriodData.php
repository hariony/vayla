<?php

namespace App\Data\Invoices;

use Spatie\LaravelData\Data;

/** Le mois facturé : premier et dernier jour (`AAAA-MM-JJ`), et « août 2026 ». */
final class InvoicePeriodData extends Data
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly string $label,
    ) {}

    /** « AAAA-MM » */
    public function mois(): string
    {
        return substr($this->from, 0, 7);
    }
}
