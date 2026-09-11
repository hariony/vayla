<?php

namespace App\Services\Verification;

use App\Contracts\Verification\CodeSender;
use App\Enums\VerificationKind;
use App\Exceptions\CodeSendingFailed;
use App\Mail\CodeMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * L'envoi du code par e-mail.
 *
 * **C'est le seul canal qui part automatiquement aujourd'hui.** WhatsApp
 * exige une entreprise vérifiée chez Meta, le SMS un compte payant : l'e-mail
 * ne demande qu'un serveur d'envoi, et il en existe de gratuits. C'est ce qui
 * en fait le canal d'inscription, pour le voyageur comme pour le propriétaire.
 *
 * **Un code, pas un lien.** Un lien de vérification ouvre le navigateur par
 * défaut du téléphone, qui n'est pas toujours celui où la session a été
 * ouverte — l'utilisateur se retrouve déconnecté sur une page qui dit
 * « vérifié » sans que rien n'avance. Six chiffres se recopient d'une
 * application à l'autre sans rien perdre.
 */
class MailCodeSender implements CodeSender
{
    public function envoyer(string $destination, string $code): void
    {
        try {
            Mail::to($destination)->send(new CodeMail($code));
        } catch (Throwable $e) {
            // Le détail va aux journaux, jamais à l'écran : un refus SMTP
            // n'apprend rien à quelqu'un qui attend un message.
            Log::error('[Code e-mail] '.$e->getMessage());

            throw new CodeSendingFailed('Le message n’a pas pu partir. Réessayez dans un instant.');
        }
    }

    public function canal(): string
    {
        return 'mail';
    }

    public function sert(VerificationKind $kind): bool
    {
        return $kind === VerificationKind::Email;
    }
}
