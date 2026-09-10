<?php

namespace App\Services\Verification;

use App\Enums\VerificationKind;
use App\Support\Telephone;
use Illuminate\Support\Facades\Log;

/**
 * Le canal de développement : le code part dans les journaux, pas sur un
 * téléphone.
 *
 * **Aucun message réel, aucun coût, et surtout aucun envoi accidentel vers un
 * vrai numéro** pendant qu'on met au point l'écran. C'est le pilote par
 * défaut : pour envoyer pour de bon, il faut le choisir explicitement dans la
 * configuration, jamais l'obtenir par oubli.
 */
class LogCodeSender implements CodeSender
{
    public function envoyer(string $destination, string $code): void
    {
        $numero = Telephone::depuis($destination);

        if (! $numero) {
            throw new CodeSendingFailed('Ce numéro n’est pas utilisable.');
        }

        Log::info("[OTP] {$numero->lisible()} → {$code}");
    }

    public function sert(VerificationKind $kind): bool
    {
        return $kind === VerificationKind::Phone;
    }

    public function canal(): string
    {
        return 'log';
    }
}
