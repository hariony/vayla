<?php

namespace App\Http\Controllers\Office;

use App\Contracts\Office\AdminPasswords;
use App\Http\Requests\Office\OfficeMemberRequest;
use App\Models\Admin;
use App\Services\Office\Team\TeamMembers;
use App\Services\Office\Team\TeamQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Les membres de l'équipe. **Le mot de passe provisoire revient une seule
 * fois**, en session flash : il s'affiche à celui qui vient d'ajouter le membre,
 * et disparaît au rechargement suivant. Il n'est écrit nulle part ailleurs.
 */
class TeamController extends OfficeController
{
    public function index(Request $request, TeamQuery $equipe): Response
    {
        return Inertia::render('Office/Team/Index', $equipe->page($this->admin($request)));
    }

    public function store(OfficeMemberRequest $request, TeamMembers $membres): RedirectResponse
    {
        $nouveau = $membres->ajouter($this->admin($request), $request->toDto());

        return back()
            ->with('succes', "{$nouveau->membre->name} fait partie de l’équipe. Transmettez-lui son mot de passe provisoire de vive voix.")
            ->with('provisoire', ['name' => $nouveau->membre->name, 'email' => $nouveau->membre->email, 'password' => $nouveau->motDePasse]);
    }

    public function reset(Request $request, Admin $membre, AdminPasswords $motsDePasse): RedirectResponse
    {
        $motDePasse = $motsDePasse->reinitialiser($this->admin($request), $membre);

        return back()
            ->with('succes', "Nouveau mot de passe provisoire pour {$membre->name}. Il en choisira un à sa prochaine connexion.")
            ->with('provisoire', ['name' => $membre->name, 'email' => $membre->email, 'password' => $motDePasse]);
    }

    public function destroy(Request $request, Admin $membre, TeamMembers $membres): RedirectResponse
    {
        $membres->retirer($this->admin($request), $membre);

        return back()->with('succes', "{$membre->name} n’a plus accès au back-office.");
    }
}
