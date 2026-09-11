<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficeSettlementRequest;
use App\Models\Owner;
use App\Services\Office\OfficeActions;
use App\Services\Office\OfficeReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends OfficeController
{
    public function __construct(
        private OfficeActions $actions,
    ) {}

    /** Le mois dernier par défaut : c'est celui dont les factures viennent de partir. */
    public function index(Request $request, OfficeReadService $lecture): Response
    {
        $mois = $this->mois((string) $request->query('mois')) ?? Carbon::today()->subMonthNoOverflow();

        return Inertia::render('Office/Invoices/Index', $lecture->facturation($mois));
    }

    public function settle(OfficeSettlementRequest $request): RedirectResponse
    {
        $owner = Owner::findOrFail($request->validated('owner_id'));

        $this->actions->reglerFacture($this->admin($request), $owner, $this->mois($request->validated('mois')), $request->validated('reference'));

        return back()->with('succes', "Règlement de {$owner->name} consigné.");
    }

    public function reopen(OfficeSettlementRequest $request): RedirectResponse
    {
        $owner = Owner::findOrFail($request->validated('owner_id'));

        $this->actions->rouvrirFacture($this->admin($request), $owner, $this->mois($request->validated('mois')));

        return back()->with('succes', 'Règlement annulé : la facture est de nouveau due.');
    }

    /** Un mois `AAAA-MM`, jamais dans le futur. Une saisie illisible retombe sur le défaut. */
    private function mois(string $saisie): ?Carbon
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $saisie)) {
            return null;
        }

        $mois = Carbon::createFromFormat('Y-m-d', $saisie.'-01')->startOfDay();

        return $mois->gt(Carbon::today()->startOfMonth()) ? null : $mois;
    }
}
