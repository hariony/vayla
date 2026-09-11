<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tout ce que le back-office ne connaît pas.
 *
 * **Sans cette route, l'hôte du back-office servirait le site public** : les
 * routes du site n'ont pas de domaine, elles répondent sur n'importe quel
 * hôte. `office.…/logements` ouvrirait le catalogue, avec l'en-tête et le
 * menu du compte d'un voyageur, dans l'outil de l'équipe. C'est un contrôleur
 * et non une fermeture : le cache de routes ne sait pas sérialiser une
 * fermeture.
 */
class NotFoundController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Office/NotFound')
            ->toResponse($request)
            ->setStatusCode(404);
    }
}
