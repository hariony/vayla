<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\NewOwnerRequest;
use App\Http\Requests\Office\OwnerQueueRequest;
use App\Models\Owner;
use App\Services\Office\Owners\OwnerDetailQuery;
use App\Services\Office\Owners\OwnerEnrollment;
use App\Services\Office\Owners\OwnerQueueQuery;
use App\Services\Office\Owners\OwnerSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Les propriétaires : la liste, la fiche, l'inscription par l'équipe, et les deux gestes de l'appel de vérification. */
class OwnerController extends OfficeController
{
    public function index(OwnerQueueRequest $request, OwnerQueueQuery $file): Response
    {
        return Inertia::render('Office/Owners/Index', $file->page($request->toDto()));
    }

    public function store(NewOwnerRequest $request, OwnerEnrollment $inscription): RedirectResponse
    {
        $owner = $inscription->inscrire($this->admin($request), $request->toDto());

        return redirect()->route('office.owners.show', $owner)
            ->with('succes', 'Compte créé. Son lien d’accès est dans la file WhatsApp : il reste à l’envoyer.');
    }

    public function show(Owner $owner, OwnerDetailQuery $fiche): Response
    {
        return Inertia::render('Office/Owners/Show', $fiche->page($owner));
    }

    public function verify(Request $request, Owner $owner, OwnerSupport $support): RedirectResponse
    {
        $support->verifierTelephone($this->admin($request), $owner);

        return back()->with('succes', 'Numéro vérifié : les niveaux 2 et 3 sont ouverts pour ses annonces.');
    }

    public function link(Request $request, Owner $owner, OwnerSupport $support): RedirectResponse
    {
        $support->renvoyerLien($this->admin($request), $owner);

        return back()->with('succes', 'Lien d’accès dans la file WhatsApp : il reste à l’envoyer.');
    }
}
