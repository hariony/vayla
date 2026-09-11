<?php

namespace App\Repositories;

use App\Contracts\Repositories\OutboundMessageRepositoryInterface;
use App\Enums\NotificationKind;
use App\Models\Booking;
use App\Models\OutboundMessage;
use App\Models\Owner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OutboundMessageRepository implements OutboundMessageRepositoryInterface
{
    public function enfiler(NotificationKind $motif, string $destinataire, string $corps, ?Owner $owner, ?Booking $booking): OutboundMessage
    {
        return OutboundMessage::create([
            'kind' => $motif,
            'to' => $destinataire,
            'body' => $corps,
            'owner_id' => $owner?->id,
            'booking_id' => $booking?->id,
        ]);
    }

    public function dejaEnfile(NotificationKind $motif, ?Booking $booking): bool
    {
        if (! $booking) {
            return false;
        }

        return OutboundMessage::query()
            ->where('kind', $motif->value)
            ->where('booking_id', $booking->id)
            ->exists();
    }

    /**
     * Ce qui reste à envoyer, **l'urgent d'abord**.
     *
     * Celui qui envoie à la main n'a pas le temps de trier : une demande qui
     * expire dans deux heures ne peut pas attendre derrière dix liens d'accès.
     */
    public function enAttente(int $limite = 50): Collection
    {
        $urgents = array_values(array_map(
            fn (NotificationKind $k) => $k->value,
            array_filter(NotificationKind::cases(), fn (NotificationKind $k) => $k->urgent()),
        ));

        return OutboundMessage::query()
            ->with(['owner', 'booking'])
            ->whereNull('sent_at')
            ->orderByRaw('case when kind in (\''.implode("','", $urgents).'\') then 0 else 1 end')
            ->orderBy('created_at')
            ->limit($limite)
            ->get();
    }

    public function derniers(int $limite = 50): Collection
    {
        return OutboundMessage::query()->with(['owner', 'booking'])->latest('id')->limit($limite)->get();
    }

    public function trouver(int $id): ?OutboundMessage
    {
        return OutboundMessage::query()->find($id);
    }

    public function marquerEnvoye(OutboundMessage $message): void
    {
        $message->forceFill(['sent_at' => Carbon::now(), 'failure' => null])->save();
    }
}
