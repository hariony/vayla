<?php

namespace App\Data\Office\Invoices;

use Spatie\LaravelData\Data;

/** Le mois affiché, et ses voisins. Pas de mois suivant après le mois en cours. */
final class InvoiceMonthData extends Data
{
    public function __construct(
        public readonly string $cle,
        public readonly string $label,
        public readonly bool $enCours,
        public readonly string $precedent,
        public readonly ?string $suivant,
    ) {}
}
