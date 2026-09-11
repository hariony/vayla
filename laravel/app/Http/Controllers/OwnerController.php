<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingRefusedException;
use App\Http\Requests\OwnerDeclineRequest;
use App\Models\Owner;
use App\Services\Owners\OwnerDashboardQuery;
use App\Services\Owners\OwnerResponses;
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
    public function show(Request $request, OwnerDashboardQuery $tableau): Response
    {
        return Inertia::render('Owner/Index', $tableau->page($this->proprietaire($request)));
    }

    public function accept(Request $request, string $reference, OwnerResponses $reponses): RedirectResponse
    {
        try {
            $reponses->accepter($this->proprietaire($request), $reference);
        } catch (BookingRefusedException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        return back()->with('succes', 'Demande acceptée. Le voyageur est prévenu et vos dates sont bloquées.');
    }

    public function decline(OwnerDeclineRequest $request, string $reference, OwnerResponses $reponses): RedirectResponse
    {
        try {
            $reponses->refuser($this->proprietaire($request), $reference, $request->motif());
        } catch (BookingRefusedException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        return back()->with('succes', 'Demande refusée. Les nuits sont rendues à votre calendrier.');
    }

    private function proprietaire(Request $request): Owner
    {
        return $request->user('proprietaire');
    }
}
