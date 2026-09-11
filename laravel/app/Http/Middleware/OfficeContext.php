<?php

namespace App\Http\Middleware;

use App\Data\Office\Shell\OfficeAdminData;
use App\Services\Office\OfficeCountersQuery;
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
 * **Le partagé : l'équipier connecté et les compteurs de la colonne**
 * (`OfficeCountersQuery`) — les files qui attendent quelqu'un.
 */
class OfficeContext
{
    public function __construct(private OfficeCountersQuery $compteurs) {}

    public function handle(Request $request, Closure $next): Response
    {
        URL::forceRootUrl(config('app.url'));

        Inertia::share([
            'admin' => fn () => ($a = $request->user('admin')) ? OfficeAdminData::fromModel($a) : null,
            // Le mot de passe provisoire d'un collègue, **une seule fois** : il
            // vit dans la session flash et disparaît au rechargement suivant.
            'provisoire' => fn () => $request->user('admin') ? $request->session()->get('provisoire') : null,
            'officeCompteurs' => fn () => $request->user('admin') ? $this->compteurs->compter() : null,
        ]);

        return $next($request);
    }
}
