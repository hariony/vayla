<?php

namespace App\Data\Owners;

use App\Data\Invoices\InvoiceData;
use Spatie\LaravelData\Data;

/**
 * Les props de `Owner/Index`, **ordonnées par urgence** : les demandes à
 * répondre — le seul bloc qui porte des boutons —, les séjours à venir, les
 * logements, la facture.
 */
final class OwnerDashboardPageData extends Data
{
    /**
     * @param  list<DashboardBookingData>  $pending
     * @param  list<DashboardBookingData>  $upcoming
     * @param  list<DashboardListingData>  $listings
     */
    public function __construct(
        public readonly OwnerSummaryData $owner,
        public readonly array $pending,
        public readonly array $upcoming,
        public readonly array $listings,
        public readonly InvoiceData $invoice,
        public readonly bool $demo,
    ) {}
}
