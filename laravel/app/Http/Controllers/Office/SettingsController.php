<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficeSettingsRequest;
use App\Services\Office\OfficeContentReadService;
use App\Services\Office\OfficeContentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends OfficeController
{
    public function __construct(
        private OfficeContentService $contenu,
    ) {}

    public function index(OfficeContentReadService $lecture): Response
    {
        return Inertia::render('Office/Settings/Index', $lecture->reglages());
    }

    public function rate(OfficeSettingsRequest $request): RedirectResponse
    {
        $this->contenu->changerTauxEuro($this->admin($request), (float) $request->validated('eur_rate'), $request->validated('eur_rate_date'));

        return back()->with('succes', 'Taux de change enregistré. Il s’affiche sur les fiches dès maintenant.');
    }

    public function commission(OfficeSettingsRequest $request): RedirectResponse
    {
        $this->contenu->changerCommission($this->admin($request), (float) $request->validated('commission'));

        return back()->with('succes', 'Commission enregistrée. Elle vaut pour les nouvelles demandes ; les réservations existantes gardent la leur.');
    }
}
