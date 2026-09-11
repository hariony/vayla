<?php

namespace App\Services\Owners;

use App\Contracts\Invoices\InvoiceCalculator;
use App\Contracts\Settings\SettingsStore;
use App\Data\Owners\OwnerInvoicesPageData;
use App\Models\Owner;

/**
 * La facturation du propriétaire : **le mois en cours d'abord, et ce n'en est
 * pas une** — ce qui s'accumule, qu'il puisse voir venir —, puis ses factures,
 * sans les mois vides.
 */
final class OwnerInvoicesQuery
{
    public function __construct(
        private InvoiceCalculator $factures,
        private SettingsStore $reglages,
    ) {}

    public function page(Owner $owner): OwnerInvoicesPageData
    {
        return new OwnerInvoicesPageData($this->factures->historique($owner), $this->reglages->commission());
    }
}
