<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficeSettingsRequest;
use App\Services\Office\Content\SettingsEditor;
use App\Services\Office\Content\SettingsQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Les réglages : le taux de change et sa date, la commission des nouvelles demandes. */
class SettingsController extends OfficeController
{
    public function index(SettingsQuery $lecture): Response
    {
        return Inertia::render('Office/Settings/Index', $lecture->page());
    }

    public function rate(OfficeSettingsRequest $request, SettingsEditor $reglages): RedirectResponse
    {
        $reglages->changerTauxEuro($this->admin($request), $request->tauxEuro());

        return back()->with('succes', 'Taux de change enregistré. Il s’affiche sur les fiches dès maintenant.');
    }

    public function commission(OfficeSettingsRequest $request, SettingsEditor $reglages): RedirectResponse
    {
        $reglages->changerCommission($this->admin($request), $request->commission());

        return back()->with('succes', 'Commission enregistrée. Elle vaut pour les nouvelles demandes ; les réservations existantes gardent la leur.');
    }
}
