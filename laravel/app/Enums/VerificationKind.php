<?php

namespace App\Enums;

/**
 * Ce qu'un code à usage unique vérifie.
 *
 * **Ce n'est pas le canal.** Un code de téléphone part par WhatsApp ou par
 * SMS selon la configuration, sans que ce qu'il prouve change. Confondre les
 * deux ferait qu'un changement de fournisseur invaliderait des vérifications
 * déjà acquises.
 */
enum VerificationKind: string
{
    case Email = 'email';
    case Phone = 'phone';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'adresse e-mail',
            self::Phone => 'numéro de téléphone',
        };
    }
}
