<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\SignupSourceRequest;
use App\Services\Owners\OwnerLandingQuery;
use App\Services\Owners\OwnerSignup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * **La page où mène la publicité** : `/louer-mon-logement`.
 *
 * Elle ne vend pas un catalogue qui n'existe pas encore : elle dit ce que
 * Vayla fait d'une annonce, ce que ça coûte (rien tant qu'aucun voyageur n'a
 * séjourné) et ce qui se passe après l'inscription. Un seul bouton, vers la
 * porte existante. Pendant la collecte, c'est aussi là que renvoie tout le
 * reste du site.
 *
 * La source du lien est retenue ici, écrite à la naissance du compte.
 */
class LandingController extends Controller
{
    public function show(SignupSourceRequest $request, OwnerSignup $signup, OwnerLandingQuery $vitrine): Response
    {
        $signup->retenirSource($request->source());

        return Inertia::render('Owners/Landing', $vitrine->page());
    }

    /**
     * **`/proprietaires`, l'ancienne adresse**, déjà partagée dans des messages
     * et peut-être dans une publicité : elle ne doit jamais tomber. Redirection
     * permanente, **paramètres compris** — sans eux, la source de la publicité
     * se perdrait au passage et l'inscription n'aurait plus d'origine.
     */
    public function ancienne(Request $request): RedirectResponse
    {
        return redirect()->route('owners.landing', $request->query(), 301);
    }
}
