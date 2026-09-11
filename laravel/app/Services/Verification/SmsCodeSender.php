<?php

namespace App\Services\Verification;

use App\Contracts\Verification\CodeSender;
use App\Enums\VerificationKind;
use App\Exceptions\CodeSendingFailed;
use App\Support\Telephone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * L'envoi automatique par SMS (Twilio).
 *
 * **Le canal de repli, pas le canal cible.** Il coûte à chaque message, et la
 * livraison A2P vers Madagascar passe par des opérateurs qui filtrent parfois
 * les expéditeurs étrangers — un code qui n'arrive pas est pire qu'un code
 * qu'on n'a pas promis. Il reste utile pour un propriétaire sans WhatsApp,
 * cas rare mais réel.
 *
 * Un compte Twilio se crée **sans entreprise enregistrée** : une carte
 * bancaire suffit. C'est ce qui en fait le seul canal automatique disponible
 * avant que Vayla n'ait sa société.
 */
class SmsCodeSender implements CodeSender
{
    public function envoyer(string $destination, string $code): void
    {
        $numero = Telephone::depuis($destination);

        if (! $numero) {
            throw new CodeSendingFailed('Ce numéro n’est pas utilisable.');
        }

        $sid = config('vayla.otp.sms.sid');
        $token = config('vayla.otp.sms.token');
        $expediteur = config('vayla.otp.sms.from');

        if (! $sid || ! $token || ! $expediteur) {
            throw new CodeSendingFailed('Le canal SMS n’est pas configuré.');
        }

        try {
            $reponse = Http::withBasicAuth($sid, $token)
                ->timeout(15)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'To' => $numero->e164(),
                    'From' => $expediteur,
                    // Le nom du service est dans le message : un code nu ne dit
                    // pas de quoi il ouvre la porte, et c'est exactement ce
                    // qu'exploite l'hameçonnage par téléphone.
                    'Body' => "Vayla : votre code est {$code}. Il expire dans "
                        .config('vayla.otp.ttl_minutes').' minutes. Ne le communiquez à personne.',
                ]);
        } catch (ConnectionException $e) {
            throw new CodeSendingFailed('Le SMS n’a pas pu partir. Réessayez dans un instant.');
        }

        if ($reponse->failed()) {
            Log::error('[OTP SMS] '.$reponse->status().' '.$reponse->body());

            throw new CodeSendingFailed('Le SMS n’a pas pu partir. Réessayez dans un instant.');
        }
    }

    public function sert(VerificationKind $kind): bool
    {
        return $kind === VerificationKind::Phone;
    }

    public function canal(): string
    {
        return 'sms';
    }
}
