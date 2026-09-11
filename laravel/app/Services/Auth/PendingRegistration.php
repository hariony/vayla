<?php

namespace App\Services\Auth;

use App\Data\Auth\CodePageData;
use App\Enums\VerificationKind;
use App\Exceptions\CodeSendingFailed;
use App\Exceptions\CodeThrottled;
use App\Services\Verification\VerificationCodeService;
use Illuminate\Support\Facades\Session;

/**
 * Une inscription commencée, pas encore ouverte.
 *
 * **Le compte n'existe pas tant que le code n'est pas saisi**, et c'est le
 * point entier. Créer une ligne « non vérifiée » à la première étape
 * permettrait à n'importe qui de **squatter une adresse** : il suffirait de
 * s'inscrire avec l'e-mail de quelqu'un d'autre pour que le vrai propriétaire
 * de l'adresse se voie refuser l'inscription. En gardant l'inscription en
 * session, une tentative abandonnée ne laisse rien derrière elle.
 *
 * **Elle ne porte plus que l'adresse** : ni nom ni mot de passe à la première
 * étape — le voyageur n'en a plus, le propriétaire donne les siens après le
 * code.
 *
 * La mécanique est la même pour le voyageur et pour le propriétaire : seule
 * la clé de session change. Deux copies auraient divergé sur le renvoi de
 * code ou sur l'expiration, et l'une des deux aurait fini par laisser passer
 * ce que l'autre refuse.
 */
class PendingRegistration
{
    public function __construct(
        private VerificationCodeService $codes,
    ) {}

    /** @throws CodeThrottled|CodeSendingFailed */
    public function ouvrir(string $cle, string $email): void
    {
        $email = mb_strtolower(trim($email));

        // Le code part **avant** d'écrire en session : si l'envoi échoue,
        // rien n'est retenu et l'utilisateur recommence proprement plutôt
        // que d'arriver sur un écran de code qu'il ne recevra jamais.
        $this->codes->demander(VerificationKind::Email, $email);

        Session::put($cle, ['email' => $email]);
    }

    /** L'adresse de l'inscription en cours, ou `null`. */
    public function email(string $cle): ?string
    {
        return Session::get($cle)['email'] ?? null;
    }

    /**
     * L'écran de saisie du code, ou `null` s'il n'y a pas d'inscription en
     * cours — l'écran renvoie alors au formulaire.
     */
    public function page(string $cle, string $action, string $renvoi, string $retour): ?CodePageData
    {
        $email = $this->email($cle);

        return $email ? new CodePageData($email, $action, $renvoi, $retour, $this->attenteAvantRenvoi($cle)) : null;
    }

    /** L'adresse prouvée, ou `null` si le code est faux, expiré, ou sans inscription. */
    public function confirmer(string $cle, string $code): ?string
    {
        $email = $this->email($cle);

        if (! $email || ! $this->codes->verifier(VerificationKind::Email, $email, $code)) {
            return null;
        }

        Session::forget($cle);

        return $email;
    }

    /** @throws CodeThrottled|CodeSendingFailed */
    public function renvoyer(string $cle): void
    {
        if ($email = $this->email($cle)) {
            $this->codes->demander(VerificationKind::Email, $email);
        }
    }

    public function abandonner(string $cle): void
    {
        Session::forget($cle);
    }

    public function attenteAvantRenvoi(string $cle): int
    {
        $email = $this->email($cle);

        return $email ? $this->codes->attenteAvantRenvoi(VerificationKind::Email, $email) : 0;
    }
}
