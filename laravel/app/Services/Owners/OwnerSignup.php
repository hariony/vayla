<?php

namespace App\Services\Owners;

use App\Contracts\Repositories\OwnerRepositoryInterface;
use App\Data\Owners\OwnerProfilePageData;
use App\DTOs\Owners\OwnerProfileDto;
use App\Models\Owner;
use App\Services\Auth\PendingSocialIdentity;
use App\Services\Auth\SocialAuthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * L'inscription du propriétaire, **en trois temps : adresse, code, fiche**. Le
 * compte naît à la troisième étape, pas au code : rien en base tant que la
 * fiche n'est pas remplie. Et **la fiche est refusée sans adresse vérifiée en
 * session** — sans ce garde, on créerait un compte en postant directement.
 */
final class OwnerSignup
{
    private const ADRESSE_VERIFIEE = 'inscription.proprietaire.verifiee';

    private const SOURCE = 'inscription.proprietaire.source';

    public function __construct(
        private OwnerRepositoryInterface $proprietaires,
        private PendingSocialIdentity $identites,
        private SocialAuthService $social,
    ) {}

    /**
     * **Trouver ou créer**, et c'est toute la mécanique de la porte unique :
     * le code vient de prouver l'adresse. Le propriétaire, s'il existe ;
     * sinon l'adresse est retenue pour la fiche, et `null`.
     */
    public function apresCode(string $email): ?Owner
    {
        $owner = $this->proprietaires->parEmail($email);

        if (! $owner) {
            $this->retenirAdresse($email);
        }

        return $owner;
    }

    /**
     * **La publicité ou le message qui a amené la personne.** Retenue à
     * l'arrivée, écrite à la naissance du compte : entre les deux, il y a une
     * adresse à taper et un code à aller lire. Une source déjà retenue n'est
     * pas écrasée par une page rechargée sans paramètre.
     */
    public function retenirSource(?string $source): void
    {
        if ($source !== null) {
            Session::put(self::SOURCE, $source);
        }
    }

    /** L'adresse prouvée — par un code, ou par un fournisseur qui l'atteste. */
    public function retenirAdresse(?string $email): void
    {
        Session::put(self::ADRESSE_VERIFIEE, $email);
    }

    public function adresseVerifiee(): ?string
    {
        return Session::get(self::ADRESSE_VERIFIEE);
    }

    /** La fiche, ou `null` sans adresse vérifiée : l'écran renvoie alors au début. */
    public function page(): ?OwnerProfilePageData
    {
        $email = $this->adresseVerifiee();

        return $email ? new OwnerProfilePageData($email) : null;
    }

    /**
     * Le compte naît. **L'identité sociale se lie ici, pas au retour du
     * fournisseur** : le compte n'existait pas encore à ce moment-là.
     *
     * @return Owner|null `null` sans adresse vérifiée en session
     */
    public function creer(OwnerProfileDto $fiche): ?Owner
    {
        $email = $this->adresseVerifiee();

        if (! $email) {
            return null;
        }

        $owner = DB::transaction(function () use ($fiche, $email) {
            $owner = $this->proprietaires->creerDepuisInscription($fiche, $email, Session::get(self::SOURCE));

            if ($identite = $this->identites->reprendre()) {
                $this->social->lier($owner, $identite);
            }

            return $owner;
        });

        Session::forget([self::ADRESSE_VERIFIEE, self::SOURCE]);

        return $owner;
    }
}
