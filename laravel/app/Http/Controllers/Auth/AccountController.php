<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\TravellerAccountRequest;
use App\Services\Travellers\TravellerSpace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le compte du voyageur.
 *
 * **Trois champs, et chacun sert deux fois.** Le nom et le prénom sont ce que
 * le propriétaire lit quand il décide d'accepter quelqu'un chez lui ; le
 * numéro est ce par quoi il rappelle. Les trois **pré-remplissent la demande
 * de séjour** : sans ça, ils ne seraient qu'un dossier de plus à constituer,
 * et Vayla n'en constitue pas.
 *
 * **Nom et prénom sont séparés parce qu'un champ unique ne se relit pas.**
 * « RAKOTOBE Jean » est une écriture courante ici, « Jean Rakotobe » l'est
 * ailleurs : l'écran des réservations en avait fait « Bonjour RAKOTOBE ».
 *
 * **L'adresse est affichée mais pas modifiable** : elle est l'identifiant de
 * connexion *et* ce qui rattache les séjours au compte. La changer d'un
 * formulaire détacherait des réservations déjà faites. L'écran l'écrit plutôt
 * que d'afficher un champ grisé — un champ qu'on ne peut pas remplir sans
 * savoir pourquoi est une impasse.
 */
class AccountController extends Controller
{
    public function __construct(
        private TravellerSpace $espace,
    ) {}

    public function edit(Request $request): Response
    {
        return Inertia::render('Auth/Account', $this->espace->compte($request->user('web')));
    }

    public function update(TravellerAccountRequest $request): RedirectResponse
    {
        $this->espace->modifier($request->user('web'), $request->toDto());

        return back()->with('succes', 'Vos informations sont à jour.');
    }
}
