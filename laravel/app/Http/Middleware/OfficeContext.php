<?php

namespace App\Http\Middleware;

use App\Enums\BookingStatus;
use App\Enums\ListingStatus;
use App\Enums\StayRequestStatus;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\OutboundMessage;
use App\Models\StayRequest;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ce que toutes les pages du back-office partagent, et un piège désamorcé.
 *
 * **Le piège : les liens vers le site public.** Les routes du site n'ont pas
 * de domaine ; générées depuis une requête du back-office, elles prennent son
 * hôte. Le lien d'accès qu'un administrateur remet dans la file WhatsApp
 * serait devenu `office.…/proprietaire/acces/…` — une page introuvable envoyée
 * à un propriétaire. La racine des URL est donc ramenée à `APP_URL` : les
 * routes du back-office, elles, portent leur domaine et n'en dépendent pas.
 *
 * **Le partagé : l'équipier connecté et les trois compteurs de la colonne.**
 * Ce sont les trois files qui attendent quelqu'un — annonces à vérifier,
 * demandes en attente, messages WhatsApp à envoyer. Les afficher sur chaque
 * écran évite d'avoir à revenir au tableau de bord pour savoir s'il reste du
 * travail.
 */
class OfficeContext
{
    public function handle(Request $request, Closure $next): Response
    {
        URL::forceRootUrl(config('app.url'));

        Inertia::share([
            'admin' => fn () => ($a = $request->user('admin')) ? [
                'name' => $a->name,
                'email' => $a->email,
                'initiales' => $a->initiales(),
            ] : null,
            // Le mot de passe provisoire d'un collègue, **une seule fois** : il
            // vit dans la session flash et disparaît au rechargement suivant.
            'provisoire' => fn () => $request->user('admin') ? $request->session()->get('provisoire') : null,
            'officeCompteurs' => fn () => $request->user('admin') ? [
                'annonces' => Listing::query()->where('status', ListingStatus::Submitted->value)->count(),
                'reservations' => Booking::query()->where('status', BookingStatus::Pending->value)->count(),
                'whatsapp' => OutboundMessage::query()->whereNull('sent_at')->count(),
                'demandes' => StayRequest::query()->where('status', StayRequestStatus::New->value)->count(),
            ] : null,
        ]);

        return $next($request);
    }
}
