<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Travellers\TravellerSpace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La connexion du voyageur, et ses réservations.
 *
 * **Le compte n'est pas obligatoire, et cet écran ne le rend pas
 * obligatoire.** Demander un séjour continue de se faire sans compte, et une
 * référence continue d'ouvrir une réservation. Ce que le compte apporte, c'est
 * de retrouver ses séjours **sans avoir gardé la référence** — et c'est tout.
 *
 * **Les réservations se rattachent par l'adresse e-mail.** Le voyageur qui a
 * réservé avant de créer son compte retrouve ses séjours dès qu'il s'inscrit
 * avec la même adresse, sans qu'on ait à recoller quoi que ce soit à la main.
 */
class TravellerAuthController extends Controller
{
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('access')->with('succes', 'Vous êtes déconnecté.');
    }

    public function bookings(Request $request, TravellerSpace $espace): Response
    {
        return Inertia::render('Auth/Bookings', $espace->reservations($request->user('web')));
    }
}
