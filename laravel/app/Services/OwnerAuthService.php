<?php

namespace App\Services;

use App\Contracts\Repositories\OwnerRepositoryInterface;
use App\Models\Owner;
use Illuminate\Support\Facades\Auth;

/**
 * L'ouverture de l'espace propriétaire.
 *
 * **Deux portes, et elles ne servent pas au même moment.**
 *
 * 1. **Le mot de passe**, au quotidien. Identifiant : le **numéro de
 *    téléphone**, comparé sur ses neuf derniers chiffres — nos propriétaires
 *    ne le retapent pas deux fois de la même façon, et refuser
 *    « 034 00 000 01 » parce qu'on attendait « +261 34 00 000 01 » serait un
 *    échec de connexion pour une histoire d'espaces.
 * 2. **Le lien d'accès**, envoyé sur WhatsApp. Il ne donne plus l'espace :
 *    il donne **le compte**. Première connexion, et récupération le jour où le
 *    mot de passe est oublié — ce qui arrivera, et ce jour-là il n'y aura ni
 *    boîte mail relevée ni fournisseur de SMS à appeler.
 *
 * Le lien reste donc la seule chose qu'un propriétaire n'a pas à mémoriser, et
 * c'est ce qui rend un mot de passe acceptable pour ce public : on ne l'enferme
 * pas dehors.
 */
class OwnerAuthService
{
    public function __construct(
        private OwnerRepositoryInterface $owners,
    ) {}

    /**
     * Connexion par le lien reçu sur WhatsApp.
     *
     * La clé ne change pas à l'usage : c'est elle qu'on renvoie en cas d'oubli,
     * et la faire tourner à chaque connexion invaliderait le lien que le
     * propriétaire garde dans sa conversation. Elle se tourne à la demande
     * (`vayla:rotate-owner-key`), quand elle a fuité.
     */
    public function connecterParCle(string $cle): ?Owner
    {
        $owner = $this->owners->findByKey($cle);

        if (! $owner) {
            return null;
        }

        $this->ouvrirSession($owner, seSouvenir: true);

        /*
         * **Le lien qui arrive prouve le numéro.** Vayla l'a envoyé sur le
         * WhatsApp du propriétaire ; s'en servir pour ouvrir son compte
         * démontre qu'il tient bien cette ligne — c'est exactement ce que
         * prouve un code à usage unique, sans fournisseur de SMS ni coût par
         * message. La vérification avait déjà lieu ; il ne manquait que de
         * la noter.
         */
        if (! $owner->telephoneVerifie()) {
            $this->owners->marquerTelephoneVerifie($owner);
        }

        return $owner;
    }

    public function deconnecter(): void
    {
        Auth::guard('proprietaire')->logout();
    }

    /**
     * On note la dernière connexion, pas chacune d'elles : la question utile
     * est « ce compte sert-il encore ? », pas « d'où s'est-il connecté ». Un
     * journal complet serait une autre fonctionnalité, et il collecterait des
     * données dont on n'a pas l'usage.
     */
    private function ouvrirSession(Owner $owner, bool $seSouvenir): void
    {
        Auth::guard('proprietaire')->login($owner, $seSouvenir);

        $this->owners->marquerConnexion($owner);
    }
}
