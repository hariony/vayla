<?php

namespace App\Contracts\Repositories;

use App\Enums\NotificationKind;
use App\Models\Booking;
use App\Models\OutboundMessage;
use App\Models\Owner;
use Illuminate\Support\Collection;

interface OutboundMessageRepositoryInterface
{
    public function enfiler(NotificationKind $motif, string $destinataire, string $corps, ?Owner $owner, ?Booking $booking): OutboundMessage;

    /** A-t-on déjà écrit ce message pour cette réservation ? Évite le doublon au réenvoi. */
    public function dejaEnfile(NotificationKind $motif, ?Booking $booking): bool;

    /** @return Collection<int, OutboundMessage> */
    public function enAttente(int $limite = 50): Collection;

    public function marquerEnvoye(OutboundMessage $message): void;

    /** @return Collection<int, OutboundMessage> les plus récents, envoyés ou non */
    public function derniers(int $limite = 50): Collection;

    public function trouver(int $id): ?OutboundMessage;
}
