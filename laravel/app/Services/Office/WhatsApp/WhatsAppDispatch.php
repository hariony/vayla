<?php

namespace App\Services\Office\WhatsApp;

use App\Contracts\Office\ActionJournal;
use App\Contracts\Repositories\OutboundMessageRepositoryInterface;
use App\Enums\AdminActionKind;
use App\Exceptions\OfficeRefusal;
use App\Models\Admin;
use App\Models\OutboundMessage;

/**
 * Marquer un message comme parti. **Seulement après l'avoir ouvert dans
 * WhatsApp** — l'écran n'allume ce bouton qu'après le premier geste : marquer
 * envoyé ce qui n'est pas parti ferait expirer une demande en silence.
 */
final class WhatsAppDispatch
{
    public function __construct(
        private OutboundMessageRepositoryInterface $file,
        private ActionJournal $journal,
    ) {}

    public function marquerEnvoye(Admin $admin, OutboundMessage $message): void
    {
        if ($message->envoye()) {
            throw new OfficeRefusal('Ce message est déjà marqué comme envoyé.');
        }

        $this->file->marquerEnvoye($message);

        $this->journal->consigner($admin, AdminActionKind::WhatsAppSent, $message,
            "{$message->kind->label()} envoyé à ".($message->owner?->name ?? $message->to).'.');
    }
}
