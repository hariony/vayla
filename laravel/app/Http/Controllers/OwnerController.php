<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingRefusedException;
use App\Models\Booking;
use App\Models\Owner;
use App\Services\BookingService;
use App\Services\OwnerSpaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le tableau de bord du propriétaire connecté.
 *
 * L'espace s'ouvre désormais par **un compte** — numéro de téléphone et mot
 * de passe — et non plus par une clé dans l'adresse. Le lien WhatsApp
 * subsiste, mais il ne donne plus l'espace : il donne le compte, à la
 * première connexion et le jour où le mot de passe est perdu.
 *
 * **Le contrôle d'appartenance ne disparaît pas pour autant.** Être connecté
 * dit qui l'on est, pas ce qu'on a le droit de toucher : chaque action
 * revérifie que la réservation porte sur un logement de ce propriétaire. Les
 * références sont courtes et se dictent au téléphone — sans ce contrôle, une
 * session valide plus une référence devinée suffiraient à répondre à la place
 * d'un confrère.
 */
class OwnerController extends Controller
{
    public function __construct(
        private OwnerSpaceService $space,
        private BookingService $bookings,
    ) {}

    public function show(Request $request): Response
    {
        return Inertia::render('Owner/Index', $this->space->dashboard($this->proprietaire($request)));
    }

    public function accept(Request $request, string $reference): RedirectResponse
    {
        return $this->repondre($request, $reference, fn (Booking $b) => $this->bookings->accept($b),
            'Demande acceptée. Le voyageur est prévenu et vos dates sont bloquées.');
    }

    public function decline(Request $request, string $reference): RedirectResponse
    {
        $motif = $request->string('reason')->trim()->value() ?: null;

        return $this->repondre($request, $reference, fn (Booking $b) => $this->bookings->decline($b, $motif),
            'Demande refusée. Les nuits sont rendues à votre calendrier.');
    }

    /** Le tronc commun : vérifier l'appartenance, agir, revenir. */
    private function repondre(Request $request, string $reference, callable $action, string $message): RedirectResponse
    {
        $owner = $this->proprietaire($request);

        $booking = Booking::query()
            ->where('reference', $reference)
            // La session seule ne suffit pas : la réservation doit porter sur
            // un logement de ce propriétaire.
            ->whereIn('listing_id', $owner->listings->pluck('id'))
            ->firstOr(fn () => abort(404));

        try {
            $action($booking);
        } catch (BookingRefusedException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        return back()->with('succes', $message);
    }

    private function proprietaire(Request $request): Owner
    {
        return $request->user('proprietaire');
    }
}
