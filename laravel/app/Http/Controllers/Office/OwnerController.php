<?php

namespace App\Http\Controllers\Office;

use App\Models\Owner;
use App\Services\Office\OfficeActions;
use App\Services\Office\OfficeReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OwnerController extends OfficeController
{
    public function __construct(
        private OfficeReadService $lecture,
        private OfficeActions $actions,
    ) {}

    public function index(Request $request): Response
    {
        $filtre = $request->query('filtre') === 'a-verifier' ? 'a-verifier' : null;
        $q = trim((string) $request->query('q'));

        return Inertia::render('Office/Owners/Index', $this->lecture->proprietaires($filtre, $q === '' ? null : mb_substr($q, 0, 80)));
    }

    public function show(Owner $owner): Response
    {
        return Inertia::render('Office/Owners/Show', $this->lecture->proprietaire($owner));
    }

    public function verify(Request $request, Owner $owner): RedirectResponse
    {
        $this->actions->verifierTelephone($this->admin($request), $owner);

        return back()->with('succes', 'Numéro vérifié : les niveaux 2 et 3 sont ouverts pour ses annonces.');
    }

    public function link(Request $request, Owner $owner): RedirectResponse
    {
        $this->actions->renvoyerLien($this->admin($request), $owner);

        return back()->with('succes', 'Lien d’accès dans la file WhatsApp : il reste à l’envoyer.');
    }
}
