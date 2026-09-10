<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Le message qui porte le code.
 *
 * **Le code est dans l'objet du message, pas seulement dans le corps.** Sur
 * un téléphone, la notification affiche l'objet : on lit le code sans même
 * ouvrir la boîte, et c'est exactement ce qu'on veut d'un code à six chiffres.
 *
 * **Aucun lien cliquable.** Un code n'a pas besoin de lien, et un e-mail de
 * vérification qui en porte un apprend à nos utilisateurs à cliquer dans les
 * messages qui parlent de compte — c'est-à-dire exactement ce que
 * l'hameçonnage exploite.
 */
class CodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->code} — votre code Vayla",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.code',
            with: ['minutes' => (int) config('vayla.otp.ttl_minutes')],
        );
    }
}
