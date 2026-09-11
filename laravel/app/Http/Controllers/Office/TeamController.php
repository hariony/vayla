<?php

namespace App\Http\Controllers\Office;

use App\Http\Requests\Office\OfficeMemberRequest;
use App\Models\Admin;
use App\Services\Office\OfficeActions;
use App\Services\Office\OfficeAuthService;
use App\Services\Office\OfficeReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * L'équipe. **On y ajoute quelqu'un, on ne l'invite pas** : aucun e-mail ne
 * part. Il reçoit un mot de passe provisoire, transmis de vive voix, et en
 * choisit un à sa première connexion.
 */
class TeamController extends OfficeController
{
    public function __construct(
        private OfficeActions $actions,
    ) {}

    public function index(Request $request, OfficeReadService $lecture): Response
    {
        return Inertia::render('Office/Team/Index', $lecture->equipe($this->admin($request)));
    }

    /**
     * Le mot de passe provisoire revient **une seule fois**, en session flash :
     * il s'affiche à celui qui vient d'ajouter le membre, et disparaît au
     * rechargement suivant. Il n'est écrit nulle part ailleurs.
     */
    public function store(OfficeMemberRequest $request): RedirectResponse
    {
        [$membre, $motDePasse] = $this->actions->ajouterMembre($this->admin($request), $request->validated('name'), $request->validated('email'));

        return back()
            ->with('succes', "{$membre->name} fait partie de l’équipe. Transmettez-lui son mot de passe provisoire de vive voix.")
            ->with('provisoire', ['name' => $membre->name, 'email' => $membre->email, 'password' => $motDePasse]);
    }

    public function reset(Request $request, Admin $membre, OfficeAuthService $auth): RedirectResponse
    {
        $motDePasse = $auth->reinitialiser($this->admin($request), $membre);

        return back()
            ->with('succes', "Nouveau mot de passe provisoire pour {$membre->name}. Il en choisira un à sa prochaine connexion.")
            ->with('provisoire', ['name' => $membre->name, 'email' => $membre->email, 'password' => $motDePasse]);
    }

    public function destroy(Request $request, Admin $membre): RedirectResponse
    {
        $this->actions->retirerMembre($this->admin($request), $membre);

        return back()->with('succes', "{$membre->name} n’a plus accès au back-office.");
    }
}
