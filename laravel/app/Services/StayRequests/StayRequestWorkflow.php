<?php

namespace App\Services\StayRequests;

use App\Contracts\Office\ActionJournal;
use App\Contracts\Repositories\StayRequestRepositoryInterface;
use App\Enums\AdminActionKind;
use App\Enums\StayRequestStatus;
use App\Exceptions\OfficeRefusal;
use App\Models\Admin;
use App\Models\StayRequest;

/**
 * Traiter une demande. **Prendre une demande l'écrit** — deux personnes qui
 * écrivent au même voyageur sans le savoir, c'est un voyageur qui reçoit deux
 * fois la même question. **La clore demande une note** pour l'équipe : ce qui
 * a été proposé, ou pourquoi rien.
 */
final class StayRequestWorkflow
{
    public function __construct(
        private StayRequestRepositoryInterface $demandes,
        private ActionJournal $journal,
    ) {}

    public function prendre(Admin $admin, StayRequest $demande): void
    {
        $this->exigerOuverte($demande, 'Cette demande est close.');

        $this->demandes->marquerPrise($demande, $admin->id);
        $this->journal->consigner($admin, AdminActionKind::StayRequestTaken, $demande, "Demande de séjour de {$demande->name} prise en charge.");
    }

    public function clore(Admin $admin, StayRequest $demande, string $note): void
    {
        $this->exigerOuverte($demande, 'Cette demande est déjà close.');

        $this->demandes->clore($demande, $admin->id, $note);
        $this->journal->consigner($admin, AdminActionKind::StayRequestClosed, $demande, "Demande de séjour de {$demande->name} close.", $note);
    }

    private function exigerOuverte(StayRequest $demande, string $refus): void
    {
        if ($demande->status === StayRequestStatus::Closed) {
            throw new OfficeRefusal($refus);
        }
    }
}
