<?php

namespace App\Services\Auth;

use App\Enums\SocialProvider;
use RuntimeException;

/**
 * Le fournisseur ne garantit pas l'adresse, et un compte Vayla la porte déjà.
 *
 * **C'est un refus de sécurité, pas une panne.** Rattacher ici reviendrait à
 * ouvrir le compte de quelqu'un à qui sait créer un compte Facebook avec son
 * adresse. L'écran renvoie donc vers l'entrée par code, qui prouve la boîte —
 * et une fois entré par là, l'utilisateur pourra lier son compte social depuis
 * une identité déjà authentifiée.
 */
class LiaisonRefusee extends RuntimeException
{
    public function __construct(
        public readonly SocialProvider $provider,
        public readonly string $email,
    ) {
        parent::__construct(
            'Un compte Vayla utilise déjà cette adresse. Pour des raisons de sécurité, '
            ."{$provider->label()} ne peut pas s’y rattacher tout seul : entrez votre adresse, "
            .'nous vous envoyons un code.'
        );
    }
}
