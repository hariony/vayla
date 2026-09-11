<?php

namespace App\Contracts\Owners;

use App\Models\Owner;

/**
 * Le lien d'accès d'un propriétaire : sa clé, et le message WhatsApp qui la
 * porte. Implémenté par `OwnerKeyService` (la clé) et `OwnerNotifier` (la file)
 * — voir `OwnerAccessLink`.
 */
interface OwnerAccess
{
    /** Remet le lien dans la file WhatsApp, en créant la clé si le propriétaire n'en a pas. */
    public function renvoyer(Owner $owner): void;
}
