<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\OwnerAuthService;
use Illuminate\Http\RedirectResponse;

/**
 * Le lien d'accès envoyé sur WhatsApp.
 *
 * **Il ouvre l'espace en un geste, sans passer par la boîte mail.** Depuis que
 * la connexion se fait par code, le propriétaire peut toujours entrer par son
 * adresse ; mais le jour où une demande expire dans quelques heures, aller
 * relever ses mails est un détour de trop. Le lien est le chemin court, et
 * c'est celui que Vayla lui envoie déjà là où il lit vraiment.
 *
 * **Il ne pose plus de mot de passe** : il n'y en a plus. La clé se retire à
 * chaque rotation (`vayla:rotate-owner-key`), ce qui est la seule chose qui
 * rendait ce lien dangereux — une conversation WhatsApp transférée.
 */
class AccessLinkController extends Controller
{
    public function __construct(
        private OwnerAuthService $auth,
    ) {}

    /** `/proprietaire/acces/{cle}` — le lien cliqué depuis WhatsApp. */
    public function entrer(string $cle): RedirectResponse
    {
        if (! $this->auth->connecterParCle($cle)) {
            return redirect()->route('owner.login')
                ->withErrors(['email' => 'Ce lien n’est plus valable. Entrez votre adresse pour recevoir un code.']);
        }

        return redirect()->route('owner.home');
    }
}
