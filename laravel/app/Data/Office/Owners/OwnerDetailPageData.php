<?php

namespace App\Data\Office\Owners;

use Spatie\LaravelData\Data;

/** Les props de `Office/Owners/Show`. */
final class OwnerDetailPageData extends Data
{
    public function __construct(
        public readonly OwnerDetailData $proprietaire,
        public readonly array $annonces,
        public readonly array $reservations,
        public readonly OwnerInvoicesData $factures,
        public readonly array $journal,
    ) {}
}
