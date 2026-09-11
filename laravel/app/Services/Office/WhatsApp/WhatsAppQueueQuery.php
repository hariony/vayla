<?php

namespace App\Services\Office\WhatsApp;

use App\Contracts\Repositories\OfficeWhatsAppRepositoryInterface;
use App\Data\Office\ListFilterData;
use App\Data\Office\OwnerRefData;
use App\Data\Office\PaginatedData;
use App\Data\Office\TabData;
use App\Data\Office\WhatsApp\BookingRefData;
use App\Data\Office\WhatsApp\OutboundMessageRowData;
use App\Data\Office\WhatsApp\WhatsAppPageData;
use App\Models\OutboundMessage;
use App\Support\Telephone;

/** La file WhatsApp : ce qui reste à envoyer à la main, l'urgent devant. */
final class WhatsAppQueueQuery
{
    private const PAR_PAGE = 30;

    public function __construct(private OfficeWhatsAppRepositoryInterface $messages) {}

    public function page(bool $envoyes): WhatsAppPageData
    {
        return new WhatsAppPageData(
            messages: PaginatedData::fromPaginator($this->messages->paginer($envoyes, self::PAR_PAGE), fn (OutboundMessage $m) => new OutboundMessageRowData(
                id: $m->id,
                kind: $m->kind->label(),
                urgent: $m->kind->urgent(),
                to: Telephone::depuis($m->to)?->lisible() ?? $m->to,
                owner: $m->owner ? new OwnerRefData($m->owner->id, $m->owner->name) : null,
                booking: $m->booking ? new BookingRefData($m->booking->reference) : null,
                body: $m->body,
                lien: $m->lienWhatsApp(),
                createdAt: $m->created_at->toIso8601String(),
                sentAt: $m->sent_at?->toIso8601String(),
            )),
            onglets: [
                new TabData('a-envoyer', 'À envoyer', $this->messages->nombre(envoyes: false)),
                new TabData('envoyes', 'Envoyés', $this->messages->nombre(envoyes: true)),
            ],
            filtre: new ListFilterData($envoyes ? 'envoyes' : 'a-envoyer', ''),
        );
    }
}
