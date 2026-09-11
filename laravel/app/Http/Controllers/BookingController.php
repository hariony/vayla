<?php

namespace App\Http\Controllers;

use App\Enums\MessageAuthor;
use App\Exceptions\BookingRefusedException;
use App\Http\Requests\BookingRequest;
use App\Http\Requests\MessageRequest;
use App\Models\Booking;
use App\Services\BookingPageService;
use App\Services\ConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function __construct(
        private BookingPageService $service,
        private ConversationService $conversations,
    ) {}

    public function create(Request $request, string $slug): Response
    {
        return Inertia::render('Bookings/Create', $this->service->form(
            $slug,
            $request->query('arrivee'),
            $request->query('depart'),
            $request->integer('voyageurs') ?: null,
            // Connecté, ses coordonnées pré-remplissent le formulaire. Le
            // compte n'est toujours pas exigé : sans lui, les champs sont
            // simplement vides.
            $request->user(),
        ));
    }

    public function store(BookingRequest $request, string $slug): RedirectResponse
    {
        try {
            $booking = $this->service->book($slug, $request->validated());
        } catch (BookingRefusedException $e) {
            // Un refus métier n'est pas une panne : il revient dans le
            // formulaire, à côté des dates, là où il se corrige.
            return back()->withInput()->withErrors(['arrival' => $e->getMessage()]);
        }

        return to_route('bookings.show', $booking->reference);
    }

    public function show(string $reference): Response
    {
        $booking = $this->reservation($reference);

        // Ouvrir la page vaut lecture : un bouton « marquer comme lu » de plus
        // n'apprendrait rien à personne.
        $this->conversations->marquerLu($booking, MessageAuthor::Traveller);

        return Inertia::render('Bookings/Confirmed', $this->service->confirmed($reference) + [
            'messages' => $this->conversations->fil($booking, MessageAuthor::Traveller),
        ]);
    }

    /**
     * Le voyageur écrit dans le fil de sa réservation.
     *
     * **Il n'a pas de compte, et c'est délibéré** : la référence tient lieu de
     * droit d'accès, comme pour la page elle-même. La limite de débit est ce
     * qui tient la porte — une référence courte se devine à force d'essais, et
     * sans elle on pourrait écrire chez des inconnus.
     */
    public function reply(MessageRequest $request, string $reference): RedirectResponse
    {
        $booking = $this->reservation($reference);

        $this->conversations->ecrire($booking, MessageAuthor::Traveller, $request->string('body')->value());

        return back()->with('succes', 'Message envoyé au propriétaire.');
    }

    private function reservation(string $reference): Booking
    {
        return Booking::query()->where('reference', $reference)->firstOr(fn () => abort(404));
    }
}
