<?php

namespace App\Repositories;

use App\Contracts\Repositories\OfficeWhatsAppRepositoryInterface;
use App\Enums\NotificationKind;
use App\Models\OutboundMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OfficeWhatsAppRepository implements OfficeWhatsAppRepositoryInterface
{
    public function paginer(bool $envoyes, int $parPage): LengthAwarePaginator
    {
        $urgents = collect(NotificationKind::cases())
            ->filter(fn (NotificationKind $k) => $k->urgent())
            ->map(fn (NotificationKind $k) => "'{$k->value}'")
            ->implode(',');

        return OutboundMessage::query()
            ->with(['owner', 'booking'])
            ->when($envoyes,
                fn (Builder $q) => $q->whereNotNull('sent_at')->latest('sent_at'),
                // Même ordre que la commande : l'urgent devant, puis le plus
                // ancien. Celui qui envoie à la main n'a pas le temps de trier.
                fn (Builder $q) => $q->whereNull('sent_at')
                    ->orderByRaw("case when kind in ({$urgents}) then 0 else 1 end")
                    ->orderBy('created_at'))
            ->paginate($parPage)
            ->withQueryString();
    }

    public function nombre(bool $envoyes): int
    {
        return OutboundMessage::query()->when($envoyes, fn ($q) => $q->whereNotNull('sent_at'), fn ($q) => $q->whereNull('sent_at'))->count();
    }
}
