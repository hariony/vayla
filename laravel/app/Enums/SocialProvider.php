<?php

namespace App\Enums;

/**
 * Les fournisseurs d'identité acceptés.
 *
 * **Un enum, pas une chaîne libre venue de l'URL.** La route porte le nom du
 * fournisseur ; sans cette liste fermée, `/auth/nimportequoi` atteindrait
 * Socialite, qui lèverait une exception de configuration — un 500 là où la
 * bonne réponse est « cette page n'existe pas ».
 *
 * **`garantitLAdresse()` est la règle de sécurité de tout le dispositif.**
 * Elle dit si le fournisseur atteste que l'adresse appartient bien à la
 * personne, et c'est la seule chose qui autorise à rattacher une identité
 * sociale à un compte Vayla existant. Une chaîne d'e-mail n'est jamais une
 * preuve : sans cette attestation, n'importe qui ouvrant un compte chez un
 * fournisseur laxiste avec l'adresse d'un tiers entrerait dans son compte.
 */
enum SocialProvider: string
{
    case Google = 'google';
    case Facebook = 'facebook';
    case Apple = 'apple';

    public function label(): string
    {
        return match ($this) {
            self::Google => 'Google',
            self::Facebook => 'Facebook',
            self::Apple => 'Apple',
        };
    }

    /**
     * Le fournisseur atteste-t-il que l'adresse est vérifiée&nbsp;?
     *
     * - **Google** publie `email_verified` dans son jeton OpenID Connect, et
     *   Socialite l'expose dans `user->user`. On lit la réponse, on ne la
     *   suppose pas.
     * - **Apple** ne transmet que des adresses vérifiées — c'est le sens même
     *   du relais privé qu'il propose — et publie `email_verified`.
     * - **Facebook** ne publie **rien** de tel. Son adresse peut n'avoir
     *   jamais été confirmée. Elle ne rattachera donc jamais à un compte
     *   existant : le lien passera par un code, comme pour tout le monde.
     */
    public function garantitLAdresse(array $brut): bool
    {
        if ($this === self::Facebook) {
            return false;
        }

        $verifie = $brut['email_verified'] ?? null;

        // OpenID Connect autorise la chaîne « true » autant que le booléen.
        return $verifie === true || $verifie === 'true';
    }
}
