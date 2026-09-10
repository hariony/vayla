<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Booking;
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
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('access')->with('succes', 'Vous êtes déconnecté.');
    }

    /** Les réservations rattachées à l'adresse du compte. */
    public function bookings(Request $request): Response
    {
        $user = $request->user();

        $reservations = Booking::query()
            ->with('listing.destination')
            ->where('traveller_email', $user->email)
            ->orderByDesc('arrival')
            ->get();

        return Inertia::render('Auth/Bookings', [
            'traveller' => ['name' => $user->name, 'email' => $user->email],
            'bookings' => $reservations->map(fn (Booking $b) => [
                'reference' => $b->reference,
                'listing' => $b->listing?->title,
                'slug' => $b->listing?->slug,
                'place' => $b->listing?->destination?->name,
                'arrival' => $b->arrival->toDateString(),
                'departure' => $b->departure->toDateString(),
                'nights' => $b->nights,
                'guests' => $b->guests,
                'total' => $b->total,
                'status' => $b->status->value,
                'statusLabel' => $b->status->label(),
            ])->all(),
        ]);
    }
}
