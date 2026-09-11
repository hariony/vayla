<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Bookings\InboxQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La boîte du voyageur.
 *
 * **Le pendant exact de celle du propriétaire**, et pour la même raison : le
 * fil ne vivait que dans la page d'une réservation, atteinte par une référence
 * reçue dans un message. Celui qui avait perdu ce message avait perdu la
 * conversation.
 *
 * **Elle mène au suivi de réservation, elle ne rend pas le fil.** Les dates,
 * le logement et le total sont là-bas, et ce sont eux qui rendent un message
 * compréhensible — « c'est possible d'arriver plus tard ? » ne veut rien dire
 * sans le séjour auquel il se rapporte.
 */
class MessageController extends Controller
{
    public function index(Request $request, InboxQuery $boites): Response
    {
        return Inertia::render('Auth/Messages', $boites->voyageur($request->user('web')->email));
    }
}
