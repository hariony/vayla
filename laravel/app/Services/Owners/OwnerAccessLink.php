<?php

namespace App\Services\Owners;

use App\Contracts\Owners\OwnerAccess;
use App\Models\Owner;
use App\Services\Notifications\OwnerNotifier;
use App\Services\OwnerKeyService;

/**
 * Remettre le lien d'accès dans la file WhatsApp. **La clé ne tourne pas** : le
 * propriétaire qui l'a perdue dans ses conversations la retrouve telle quelle.
 * La faire tourner est un geste d'incident (`vayla:rotate-owner-key`), pas de
 * support — sauf s'il n'en a jamais eu.
 */
final class OwnerAccessLink implements OwnerAccess
{
    public function __construct(
        private OwnerKeyService $cles,
        private OwnerNotifier $notifications,
    ) {}

    public function renvoyer(Owner $owner): void
    {
        if (! $owner->access_key) {
            $this->cles->tourner($owner);
        }

        $this->notifications->lienAcces($owner->refresh());
    }
}
