<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\InvoiceMonthRequest;
use App\Http\Requests\Office\OfficeSettlementRequest;
use App\Services\Office\Invoices\InvoiceSettlements;
use App\Services\Office\Invoices\InvoicesQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** La facturation d'un mois, et les règlements reçus par mobile money. */
class InvoiceController extends OfficeController
{
    public function index(InvoiceMonthRequest $request, InvoicesQuery $factures): Response
    {
        return Inertia::render('Office/Invoices/Index', $factures->page($request->mois()));
    }

    public function settle(OfficeSettlementRequest $request, InvoiceSettlements $reglements): RedirectResponse
    {
        $owner = $reglements->regler($this->admin($request), $request->toDto());

        return back()->with('succes', "Règlement de {$owner->name} consigné.");
    }

    public function reopen(OfficeSettlementRequest $request, InvoiceSettlements $reglements): RedirectResponse
    {
        $reglements->rouvrir($this->admin($request), $request->toDto());

        return back()->with('succes', 'Règlement annulé : la facture est de nouveau due.');
    }
}
