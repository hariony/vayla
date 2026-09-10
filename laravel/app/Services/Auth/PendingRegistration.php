<?php

namespace App\Services\Auth;

use App\Enums\VerificationKind;
use App\Services\Verification\VerificationCodeService;
use Illuminate\Support\Facades\Hash;
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
 * **Le mot de passe est haché dès la première étape.** Il traverse la session
 * — c'est-à-dire, selon la configuration, un fichier ou une table — et un mot
 * de passe en clair qui transite quelque part finit par y rester.
 *
 * La mécanique est la même pour le voyageur et pour le propriétaire : seule
 * la clé de session et les champs changent. Deux copies auraient divergé sur
 * le renvoi de code ou sur l'expiration, et l'une des deux aurait fini par
 * laisser passer ce que l'autre refuse.
 */
class PendingRegistration
{
    public function __construct(
        private VerificationCodeService $codes,
    ) {}

    /**
     * Retient l'inscription et envoie le code.
     *
     * @param  array<string, mixed>  $donnees  le mot de passe en clair sous `password`
     */
    public function ouvrir(string $cle, array $donnees): void
    {
        $donnees['email'] = mb_strtolower(trim($donnees['email']));

        if (isset($donnees['password'])) {
            $donnees['password'] = Hash::make($donnees['password']);
        }

        // Le code part **avant** d'écrire en session : si l'envoi échoue,
        // rien n'est retenu et l'utilisateur recommence proprement plutôt
        // que d'arriver sur un écran de code qu'il ne recevra jamais.
        $this->codes->demander(VerificationKind::Email, $donnees['email']);

        Session::put($cle, $donnees);
    }

    /** @return array<string, mixed>|null */
    public function enAttente(string $cle): ?array
    {
        return Session::get($cle);
    }

    /**
     * Vérifie le code et rend les données, ou `null`.
     *
     * La session est vidée **seulement en cas de succès** : effacer sur un
     * mauvais code obligerait à tout ressaisir pour une faute de frappe.
     *
     * @return array<string, mixed>|null
     */
    public function confirmer(string $cle, string $code): ?array
    {
        $donnees = $this->enAttente($cle);

        if (! $donnees || ! $this->codes->verifier(VerificationKind::Email, $donnees['email'], $code)) {
            return null;
        }

        Session::forget($cle);

        return $donnees;
    }

    /** Renvoie un code à l'adresse en attente. Les bornes du service s'appliquent. */
    public function renvoyer(string $cle): void
    {
        if ($donnees = $this->enAttente($cle)) {
            $this->codes->demander(VerificationKind::Email, $donnees['email']);
        }
    }

    public function abandonner(string $cle): void
    {
        Session::forget($cle);
    }

    /** Secondes avant de pouvoir redemander un code, pour l'écran. */
    public function attenteAvantRenvoi(string $cle): int
    {
        $donnees = $this->enAttente($cle);

        return $donnees
            ? $this->codes->attenteAvantRenvoi(VerificationKind::Email, $donnees['email'])
            : 0;
    }
}
