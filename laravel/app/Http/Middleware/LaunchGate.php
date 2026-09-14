<?php

namespace App\Http\Middleware;

use App\Services\Content\Pages\SitePages;
use App\Services\Support\LaunchMode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * **Pendant la collecte des logements, le site n'a qu'une porte.**
 *
 * Tout ce qui n'est pas dans `LaunchMode::ROUTES_OUVERTES` renvoie vers
 * `/louer-mon-logement` — la page où mène la publicité. Cacher les liens ne suffit
 * pas : une adresse partagée, un favori, un catalogue indexé la veille
 * ouvriraient encore les écrans qu'on a retirés des menus.
 *
 * Trois cas particuliers :
 *
 * - **`/proprietaire` mène à « Mes logements »**, pas vers la page d'arrivée :
 *   c'est l'adresse où tout propriétaire connecté atterrit (lien d'accès,
 *   connexion par code), et son tableau des demandes n'a rien à montrer.
 * - **Un geste fermé répond 404**, un écran fermé redirige : on ne renvoie pas
 *   un POST vers une page, il perdrait ce qu'il portait sans rien dire.
 * - **L'API répond 503 en JSON** : aucune application n'est sortie, et une API
 *   ouverte serait une porte dérobée sur le catalogue fermé.
 *
 * **Rien n'est indexé** pendant la collecte, pas même la page d'arrivée : une
 * annonce fictive restée dans un moteur de recherche survivrait à l'ouverture.
 *
 * Le back-office vit sur son hôte et n'est jamais touché.
 */
class LaunchGate
{
    public function __construct(
        private LaunchMode $lancement,
        private SitePages $pages,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->lancement->actif() || $request->getHost() === config('vayla.office.domaine')) {
            return $next($request);
        }

        if ($request->is('api/*')) {
            return response()->json(['message' => 'Vayla ouvre bientôt.'], 503);
        }

        if ($request->routeIs('owner.home')) {
            return redirect()->route('owner.listings');
        }

        if (! $this->ouverte($request)) {
            return $request->isMethod('GET') || $request->isMethod('HEAD')
                ? redirect()->route('owners.landing')
                : abort(404);
        }

        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        return $response;
    }

    private function ouverte(Request $request): bool
    {
        if (! $request->routeIs(...LaunchMode::ROUTES_OUVERTES)) {
            return false;
        }

        if ($request->routeIs('pages.show')) {
            return $this->pages->legalePubliee((string) $request->route('page'));
        }

        return true;
    }
}
