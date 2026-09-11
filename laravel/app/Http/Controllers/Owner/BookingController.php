<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageRequest;
use App\Http\Requests\OwnerBookingsRequest;
use App\Services\Owners\OwnerBookingsQuery;
use App\Services\Owners\OwnerResponses;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Toutes les réservations d'un propriétaire, passées comprises.
 *
 * Le tableau de bord ne montre que ce qui exige une action — les demandes à
 * répondre, les séjours à venir. Cet écran-là répond à l'autre question, celle
 * qu'on se pose une fois par mois : « qui est venu, qu'est-ce qui a été
 * refusé, qu'est-ce qui a expiré ». Sans lui, une réservation répondue
 * disparaissait de l'espace et le propriétaire n'avait plus aucune trace.
 *
 * **Le filtre est une liste de pastilles, pas un menu déroulant.** Quatre
 * états tiennent à l'écran, et un choix visible se corrige sans rouvrir quoi
 * que ce soit.
 */
class BookingController extends Controller
{
    public function index(OwnerBookingsRequest $request, OwnerBookingsQuery $historique): Response
    {
        return Inertia::render('Owner/Bookings/Index', $historique->liste($request->user('proprietaire'), $request->statut()));
    }

    public function show(Request $request, string $reference, OwnerBookingsQuery $historique): Response
    {
        return Inertia::render('Owner/Bookings/Show', $historique->fiche($request->user('proprietaire'), $reference));
    }

    public function reply(MessageRequest $request, string $reference, OwnerResponses $reponses): RedirectResponse
    {
        $reponses->ecrire($request->user('proprietaire'), $reference, $request->body());

        return back()->with('succes', 'Message envoyé au voyageur.');
    }
}
