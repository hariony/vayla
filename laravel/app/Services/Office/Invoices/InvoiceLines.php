<?php

namespace App\Services\Office\Invoices;

use App\Contracts\Invoices\InvoiceCalculator;
use App\Contracts\Repositories\InvoiceSettlementRepositoryInterface;
use App\Contracts\Repositories\OfficeOwnerRepositoryInterface;
use App\Data\Invoices\InvoiceData;
use App\Data\Office\Invoices\InvoiceSummaryData;
use App\Data\Office\Invoices\SettlementData;
use App\Models\InvoiceSettlement;
use App\Models\Owner;
use Illuminate\Support\Carbon;

/**
 * Les factures d'un mois, avec leur règlement. **Un propriétaire sans séjour
 * confirmé n'apparaît pas** : une facture à zéro n'existe pas. Lu par la
 * facturation et par le tableau de bord (factures impayées, commission du mois).
 */
final class InvoiceLines
{
    public function __construct(
        private InvoiceCalculator $factures,
        private OfficeOwnerRepositoryInterface $proprietaires,
        private InvoiceSettlementRepositoryInterface $reglements,
    ) {}

    /** @return array<int, InvoiceSummaryData> */
    public function du(Carbon $debut): array
    {
        $reglements = $this->reglements->duMois($debut);

        return $this->proprietaires->tousParNom()
            ->map(function (Owner $o) use ($debut, $reglements) {
                $facture = $this->factures->forOwner($o, $debut);

                return $facture->stays > 0 ? $this->resume($facture, $reglements[$o->id] ?? null, $o->id) : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    public function resume(InvoiceData $facture, ?InvoiceSettlement $reglement, ?int $ownerId = null): InvoiceSummaryData
    {
        return new InvoiceSummaryData(
            mois: $facture->period->mois(),
            label: $facture->period->label,
            owner: $facture->owner,
            lines: $facture->lines,
            stays: $facture->stays,
            nights: $facture->nights,
            revenue: $facture->revenue,
            due: $facture->due,
            settlement: $reglement
                ? new SettlementData($reglement->settled_at->toIso8601String(), $reglement->reference, (int) $reglement->amount)
                : null,
            ownerId: $ownerId,
        );
    }
}
