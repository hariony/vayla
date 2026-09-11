<?php

namespace App\Http\Controllers;

use App\Data\StayRequests\SentStayRequestData;
use App\Http\Requests\StayRequestPrefillRequest;
use App\Http\Requests\StayRequestRequest;
use App\Services\StayRequests\StayRequestFormQuery;
use App\Services\StayRequests\StayRequestSubmitter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** `/demande` : décrire le séjour qu'on cherche, pour que l'équipe le trouve. */
class StayRequestController extends Controller
{
    public function create(StayRequestPrefillRequest $request, StayRequestFormQuery $formulaire): Response
    {
        return Inertia::render('Demande/Create', $formulaire->page(
            $request->toDto(),
            $request->user('web'),
            $request->envoyee(),
        ));
    }

    public function store(StayRequestRequest $request, StayRequestSubmitter $demandes): RedirectResponse
    {
        $demande = $demandes->deposer($request->toDto());

        // Un tableau, pas l'objet : la session est sérialisée en JSON
        // (`session.serialization`), un objet y reviendrait en tableau.
        return redirect()->route('stay-requests.create')
            ->with(StayRequestPrefillRequest::FLASH_ENVOYEE, SentStayRequestData::fromModel($demande)->toArray());
    }
}
