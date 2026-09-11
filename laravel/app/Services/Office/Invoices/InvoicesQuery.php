<?php

namespace App\Services\Office\Invoices;

use App\Contracts\Settings\SettingsStore;
use App\Data\Office\Invoices\InvoiceMonthData;
use App\Data\Office\Invoices\InvoicesPageData;
use App\Data\Office\Invoices\InvoiceSummaryData;
use App\Data\Office\Invoices\InvoiceTotalsData;
use Illuminate\Support\Carbon;

/** La facturation d'un mois : les factures, leurs règlements, les totaux. */
final class InvoicesQuery
{
    public function __construct(
        private InvoiceLines $lignes,
        private SettingsStore $reglages,
    ) {}

    public function page(Carbon $mois): InvoicesPageData
    {
        $debut = $mois->copy()->startOfMonth();
        $courant = Carbon::today()->startOfMonth();
        $lignes = $this->lignes->du($debut);

        $du = array_sum(array_map(fn (InvoiceSummaryData $l) => $l->due, $lignes));
        $regle = array_sum(array_map(fn (InvoiceSummaryData $l) => $l->settlement ? $l->due : 0, $lignes));

        return new InvoicesPageData(
            mois: new InvoiceMonthData(
                cle: $debut->format('Y-m'),
                label: $debut->translatedFormat('F Y'),
                enCours: $debut->equalTo($courant),
                precedent: $debut->copy()->subMonthNoOverflow()->format('Y-m'),
                suivant: $debut->lt($courant) ? $debut->copy()->addMonthNoOverflow()->format('Y-m') : null,
            ),
            lignes: $lignes,
            totaux: new InvoiceTotalsData(
                du: $du,
                regle: $regle,
                reste: $du - $regle,
                sejours: array_sum(array_map(fn (InvoiceSummaryData $l) => $l->stays, $lignes)),
                revenus: array_sum(array_map(fn (InvoiceSummaryData $l) => $l->revenue, $lignes)),
            ),
            taux: $this->reglages->commission(),
        );
    }
}
