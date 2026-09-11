<?php

namespace App\Http\Controllers\Office;

use App\Models\StayRequest;
use App\Services\Office\OfficeStayRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** La file des demandes de séjour « dans l'autre sens ». */
class StayRequestController extends OfficeController
{
    public function __construct(private OfficeStayRequestService $demandes) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Office/Demandes/Index', $this->demandes->liste(
            (string) $request->query('onglet', 'nouvelles'),
            trim((string) $request->query('q')),
        ));
    }

    public function take(Request $request, StayRequest $demande): RedirectResponse
    {
        $this->demandes->prendre($this->admin($request), $demande);

        return back()->with('succes', "Vous vous occupez de la demande de {$demande->name}.");
    }

    public function close(Request $request, StayRequest $demande): RedirectResponse
    {
        $note = $request->validate(
            ['note' => ['required', 'string', 'min:5', 'max:1000']],
            ['note.required' => 'Une note pour l’équipe : ce qui a été proposé, ou pourquoi rien.', 'note.min' => 'Cinq caractères au moins.'],
        )['note'];

        $this->demandes->clore($this->admin($request), $demande, $note);

        return back()->with('succes', "Demande de {$demande->name} close.");
    }
}
