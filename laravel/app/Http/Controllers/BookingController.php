<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingRefusedException;
use App\Http\Requests\BookingFormRequest;
use App\Http\Requests\BookingRequest;
use App\Http\Requests\MessageRequest;
use App\Services\Bookings\BookingConfirmationQuery;
use App\Services\Bookings\BookingFormQuery;
use App\Services\Bookings\BookingSubmitter;
use App\Services\Bookings\TravellerThread;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La réservation côté voyageur.
 *
 * Trois écrans et rien de plus : le récapitulatif avec le formulaire, la
 * confirmation avec la référence, et c'est tout. Aucun paiement, aucun
 * compte à créer — la mise en relation est le produit.
 */
class BookingController extends Controller
{
    /** Connecté, ses coordonnées pré-remplissent le formulaire ; sans compte, les champs sont vides. */
    public function create(BookingFormRequest $request, string $slug, BookingFormQuery $formulaire): Response
    {
        return Inertia::render('Bookings/Create', $formulaire->page($slug, $request->toDto(), $request->user('web')));
    }

    public function store(BookingRequest $request, string $slug, BookingSubmitter $demandes): RedirectResponse
    {
        try {
            $booking = $demandes->soumettre($slug, $request->toDto());
        } catch (BookingRefusedException $e) {
            // Un refus métier n'est pas une panne : il revient dans le
            // formulaire, à côté des dates, là où il se corrige.
            return back()->withInput()->withErrors(['arrival' => $e->getMessage()]);
        }

        return to_route('bookings.show', $booking->reference);
    }

    public function show(string $reference, BookingConfirmationQuery $confirmation): Response
    {
        return Inertia::render('Bookings/Confirmed', $confirmation->page($reference));
    }

    /**
     * Le voyageur écrit dans le fil de sa réservation — sans compte : la
     * référence tient lieu de droit d'accès (`TravellerThread`).
     */
    public function reply(MessageRequest $request, string $reference, TravellerThread $fil): RedirectResponse
    {
        $fil->ecrire($reference, $request->body());

        return back()->with('succes', 'Message envoyé au propriétaire.');
    }
}
