<?php

namespace App\Services\Verification;

use App\Enums\VerificationKind;

/**
 * Le canal par lequel part un code à usage unique.
 *
 * **Une interface pour un seul appel**, et c'est délibéré : le canal changera.
 * L'e-mail part aujourd'hui — c'est le seul qui ne demande ni entreprise
 * enregistrée ni carte bancaire. WhatsApp et le SMS attendent derrière la même
 * porte, et le jour où ils s'ouvrent, seule la résolution du canal change :
 * ni le service, ni les bornes, ni les écrans.
 *
 * `destination` est une adresse ou un numéro en E.164 — le canal sait lequel
 * il attend, et refuse ce qui n'est pas pour lui.
 *
 * L'implémentation **lève** quand l'envoi échoue, elle ne renvoie pas `false` :
 * un code qu'on croit parti et qui n'est pas parti laisse l'utilisateur devant
 * un champ vide sans rien à y mettre. L'échec doit remonter jusqu'à l'écran.
 */
interface CodeSender
{
    /**
     * @throws CodeSendingFailed
     */
    public function envoyer(string $destination, string $code): void;

    /** Le nom du canal, retenu sur la ligne : on doit pouvoir dire par où c'est parti. */
    public function canal(): string;

    public function sert(VerificationKind $kind): bool;
}
