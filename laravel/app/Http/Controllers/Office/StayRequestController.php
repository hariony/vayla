<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\CloseStayRequestRequest;
use App\Http\Requests\Office\StayRequestQueueRequest;
use App\Models\StayRequest;
use App\Services\StayRequests\StayRequestQueueQuery;
use App\Services\StayRequests\StayRequestWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** La file des demandes de séjour « dans l'autre sens ». */
class StayRequestController extends OfficeController
{
    public function index(StayRequestQueueRequest $request, StayRequestQueueQuery $file): Response
    {
        return Inertia::render('Office/Demandes/Index', $file->page($request->toDto()));
    }

    public function take(Request $request, StayRequest $demande, StayRequestWorkflow $traitement): RedirectResponse
    {
        $traitement->prendre($this->admin($request), $demande);

        return back()->with('succes', "Vous vous occupez de la demande de {$demande->name}.");
    }

    public function close(CloseStayRequestRequest $request, StayRequest $demande, StayRequestWorkflow $traitement): RedirectResponse
    {
        $traitement->clore($this->admin($request), $demande, $request->note());

        return back()->with('succes', "Demande de {$demande->name} close.");
    }
}
