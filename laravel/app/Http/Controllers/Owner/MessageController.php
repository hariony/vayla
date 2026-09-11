<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\Bookings\InboxQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La boîte du propriétaire : toutes ses conversations, au même endroit.
 *
 * **Elle existe parce qu'un message se rate.** Les fils vivaient uniquement
 * *dans* chaque réservation : pour savoir si quelqu'un attendait une réponse,
 * il fallait ouvrir les réservations une par une, ou remarquer une pastille
 * sur un onglet qui ne disait pas laquelle. Un voyageur qui pose une question
 * trois jours avant d'arriver et n'obtient rien ne revient pas.
 *
 * **Elle ne rend pas le fil, elle y mène.** Le fil s'ouvre sur la réservation,
 * là où sont les dates, le total et les boutons de réponse — c'est le contexte
 * qui rend le message compréhensible. Une messagerie qui afficherait la
 * conversation seule obligerait à retrouver de quoi elle parle.
 */
class MessageController extends Controller
{
    public function index(Request $request, InboxQuery $boites): Response
    {
        return Inertia::render('Owner/Messages', $boites->proprietaire($request->user('proprietaire')));
    }
}
