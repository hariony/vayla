<?php

namespace App\Http\Controllers;

use App\Http\Requests\StayRequestRequest;
use App\Models\Destination;
use App\Services\StayRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `/demande` : décrire le séjour qu'on cherche, pour que l'équipe le trouve.
 *
 * Le formulaire **reprend la recherche en cours** — destination, dates,
 * voyageurs arrivent dans l'adresse depuis l'accueil — et, pour un voyageur
 * connecté, son nom et ses coordonnées : on ne redemande pas ce qu'on sait.
 */
class StayRequestController extends Controller
{
    public function __construct(private StayRequestService $demandes) {}

    public function create(Request $request): Response
    {
        $voyageur = $request->user('web');

        return Inertia::render('Demande/Create', [
            'destinations' => Destination::query()->orderBy('name')->get(['slug', 'name', 'region'])
                ->map(fn (Destination $d) => ['value' => $d->slug, 'label' => $d->name, 'region' => $d->region])->all(),
            'initial' => [
                'destination' => (string) $request->query('destination', ''),
                'arrival' => (string) $request->query('arrival', ''),
                'departure' => (string) $request->query('departure', ''),
                'guests' => max(1, min(30, (int) $request->query('guests', 2))),
                'name' => $voyageur?->name ?? '',
                'email' => $voyageur?->email ?? '',
                'phone' => $voyageur?->phone ?? '',
            ],
            // Après l'envoi : le récapitulatif, et plus de formulaire.
            'envoyee' => $request->session()->get('demandeEnvoyee'),
        ]);
    }

    public function store(StayRequestRequest $request): RedirectResponse
    {
        $demande = $this->demandes->deposer($request->validated(), $request->user('web'));

        return redirect()->route('stay-requests.create')->with('demandeEnvoyee', [
            'nom' => $demande->name,
            'canal' => $demande->phone ? 'whatsapp' : 'email',
            'contact' => $demande->phone ?? $demande->email,
        ]);
    }
}
