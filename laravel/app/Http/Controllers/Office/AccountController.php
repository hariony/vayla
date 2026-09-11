<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficePasswordRequest;
use App\Services\Office\OfficeAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Mon compte : le mot de passe, et rien d'autre.
 *
 * C'est aussi **le seul écran ouvert tant que le mot de passe est
 * provisoire** — celui posé par un collègue ou par la commande. Il a transité
 * par un autre regard ; le back-office ne s'ouvre qu'une fois la personne en a
 * choisi un à elle.
 */
class AccountController extends OfficeController
{
    public function edit(Request $request): Response
    {
        $admin = $this->admin($request);

        return Inertia::render('Office/Account', [
            'compte' => [
                'name' => $admin->name,
                'email' => $admin->email,
                'provisoire' => ! $admin->motDePasseChoisi(),
                'depuis' => $admin->password_set_at?->toIso8601String(),
            ],
        ]);
    }

    public function update(OfficePasswordRequest $request, OfficeAuthService $auth): RedirectResponse
    {
        $admin = $this->admin($request);
        $premier = ! $admin->motDePasseChoisi();

        $auth->choisir($admin, $request->validated('password'));

        // Les autres sessions de ce compte tombent : un mot de passe changé
        // parce qu'on le croit connu d'un autre ne doit pas laisser ouverte la
        // session de cet autre.
        $request->session()->regenerate();

        return $premier
            ? redirect()->route('office.home')->with('succes', 'Mot de passe choisi. Le back-office est à vous.')
            : back()->with('succes', 'Mot de passe changé.');
    }
}
