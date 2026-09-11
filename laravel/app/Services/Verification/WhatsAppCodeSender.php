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
 * L'envoi automatique par WhatsApp Cloud API (Meta).
 *
 * **C'est le canal cible** : nos propriétaires sont sur WhatsApp, un code y
 * arrive là où ils regardent déjà, et le message est gratuit à recevoir même
 * sans forfait SMS.
 *
 * Trois choses à savoir avant de brancher :
 *
 * 1. **Meta impose un modèle approuvé.** On ne peut pas écrire un texte libre
 *    à quelqu'un qui n'a jamais écrit à Vayla : il faut un modèle de catégorie
 *    « Authentication », déclaré et validé dans le gestionnaire WhatsApp. Le
 *    code est passé en paramètre du corps **et** du bouton de copie.
 * 2. **Le numéro part sans le `+`.** L'API le veut en chiffres seuls ; un `+`
 *    donne un `131026` sans que rien n'indique la cause.
 * 3. **L'identifiant est celui du numéro expéditeur** (`phone_number_id`),
 *    pas le numéro lui-même.
 *
 * Le compte de test de Meta permet de tout mettre au point sans entreprise
 * vérifiée — il n'envoie qu'à des numéros déclarés à l'avance, ce qui suffit
 * largement pour brancher et éprouver la chaîne.
 */
class WhatsAppCodeSender implements CodeSender
{
    private const VERSION = 'v21.0';

    public function envoyer(string $destination, string $code): void
    {
        $numero = Telephone::depuis($destination);

        if (! $numero) {
            throw new CodeSendingFailed('Ce numéro n’est pas utilisable.');
        }

        $token = config('vayla.otp.whatsapp.token');
        $expediteur = config('vayla.otp.whatsapp.phone_number_id');

        if (! $token || ! $expediteur) {
            throw new CodeSendingFailed('Le canal WhatsApp n’est pas configuré.');
        }

        try {
            $reponse = Http::withToken($token)
                ->timeout(15)
                ->post(sprintf('https://graph.facebook.com/%s/%s/messages', self::VERSION, $expediteur), [
                    'messaging_product' => 'whatsapp',
                    'to' => ltrim($numero->e164(), '+'),
                    'type' => 'template',
                    'template' => [
                        'name' => config('vayla.otp.whatsapp.template'),
                        'language' => ['code' => config('vayla.otp.whatsapp.lang')],
                        'components' => $this->composants($code),
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new CodeSendingFailed('WhatsApp n’a pas répondu. Réessayez dans un instant.');
        }

        if ($reponse->failed()) {
            // Le détail va aux journaux, jamais à l'écran : un code d'erreur
            // Meta n'apprend rien à quelqu'un qui attend un message.
            Log::error('[OTP WhatsApp] '.$reponse->status().' '.$reponse->body());

            throw new CodeSendingFailed('Le message n’a pas pu partir. Réessayez dans un instant.');
        }
    }

    public function sert(VerificationKind $kind): bool
    {
        return $kind === VerificationKind::Phone;
    }

    public function canal(): string
    {
        return 'whatsapp';
    }

    /**
     * Le corps porte toujours le code ; le bouton de copie seulement si le
     * modèle en a un.
     *
     * **Les deux erreurs sont symétriques et illisibles** : envoyer le
     * composant bouton à un modèle qui n'en a pas échoue, et l'omettre sur un
     * modèle qui en a un échoue aussi. Meta ne nomme la cause dans aucun des
     * deux cas. D'où un interrupteur explicite plutôt qu'une supposition.
     *
     * @return array<int, array<string, mixed>>
     */
    private function composants(string $code): array
    {
        $composants = [
            ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $code]]],
        ];

        if (config('vayla.otp.whatsapp.copy_button')) {
            $composants[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [['type' => 'text', 'text' => $code]],
            ];
        }

        return $composants;
    }
}
