<?php

namespace App\Services\Auth;

use App\Contracts\Repositories\SocialAccountRepositoryInterface;
use App\DTOs\Auth\SocialIdentityDto;
use App\Enums\EspaceSocial;
use App\Exceptions\LiaisonRefusee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Rattacher une identité sociale à un compte Vayla.
 *
 * **Toute la logique des quatre cas vit ici, une seule fois pour les trois
 * fournisseurs.** Écrite dans le contrôleur, elle aurait été recopiée par
 * fournisseur, et l'une des copies aurait fini par rattacher ce que les autres
 * refusent — c'est-à-dire par ouvrir un compte à quelqu'un d'autre.
 *
 * Les quatre cas, dans l'ordre où on les rencontre :
 *
 * 1. **L'identité est déjà connue** → on connecte, et on rafraîchit ce qui
 *    n'engage rien (nom, avatar). Jamais l'adresse du compte : elle est notre
 *    identifiant, et un fournisseur n'a pas à la déplacer.
 * 2. **Une adresse *garantie* désigne un compte existant** → on rattache. La
 *    garantie est celle du fournisseur (`email_verified`), pas la nôtre :
 *    voir `SocialProvider::garantitLAdresse()`.
 * 3. **Une adresse *non garantie* désigne un compte existant** → on ne
 *    rattache **rien**. C'est le seul cas dangereux du dispositif : sans cette
 *    barrière, ouvrir un compte Facebook avec l'adresse d'un tiers suffirait à
 *    entrer chez lui. Le service refuse, et l'écran renvoie vers l'entrée par
 *    code — qui, elle, prouve la boîte.
 * 4. **Rien ne correspond** → on crée le compte et l'identité, puis on
 *    connecte.
 *
 * **Tout se fait dans une transaction.** Un compte créé sans son identité
 * sociale serait un compte fantôme : sans adresse — cas possible — plus
 * personne ne pourrait jamais y entrer.
 */
class SocialAuthService
{
    public function __construct(
        private SocialAccountRepositoryInterface $liens,
    ) {}

    /** @throws LiaisonRefusee une adresse déjà connue, que le fournisseur n'atteste pas */
    public function rattacher(SocialIdentityDto $identite, EspaceSocial $espace): SocialAttachment
    {
        $modele = $espace->modele();

        return DB::transaction(function () use ($identite, $modele, $espace) {
            // ── 1. L'identité est déjà connue **dans cet espace**. Le type est
            // dans la clé : la même personne peut être voyageuse et
            // propriétaire avec le même compte Google, et ce sont bien deux
            // identités distinctes.
            if ($lien = $this->liens->lien($modele, $identite)) {
                $this->liens->rafraichir($lien, $identite);

                return new SocialAttachment($lien->compte, nouveau: false);
            }

            // ── 2 et 3. Une adresse désigne peut-être un compte existant.
            $existant = $identite->email ? $this->liens->compteParEmail($modele, $identite->email) : null;

            if ($existant && ! $identite->verifie) {
                throw new LiaisonRefusee($identite->provider, $identite->email);
            }

            // **Un propriétaire ne se crée pas ici.** `owners.phone` est
            // obligatoire — c'est par là que Vayla appelle pour la
            // vérification, et une annonce sans numéro joignable ne dépasse
            // jamais le niveau 1. On rend donc la main : le contrôleur envoie
            // sur la fiche, qui crée le compte **et** le lien, exactement comme
            // pour celui qui arrive par un code.
            if (! $existant && $espace === EspaceSocial::Proprietaire) {
                return new SocialAttachment(null, nouveau: true);
            }

            $compte = $existant ?? $this->liens->creerCompte($modele, $identite);
            $this->liens->lier($compte, $identite);

            return new SocialAttachment($compte, nouveau: $existant === null);
        });
    }

    /** Pose le lien sur un compte qui vient de naître — le propriétaire, après sa fiche. */
    public function lier(Model $compte, SocialIdentityDto $identite): void
    {
        $this->liens->lier($compte, $identite);
    }
}
