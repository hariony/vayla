<?php

namespace App\Contracts\Repositories;

use App\Models\OutboundMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** La file WhatsApp, vue par qui envoie à la main. */
interface OfficeWhatsAppRepositoryInterface
{
    /** @return LengthAwarePaginator<int, OutboundMessage> à envoyer : l'urgent devant, puis le plus ancien */
    public function paginer(bool $envoyes, int $parPage): LengthAwarePaginator;

    public function nombre(bool $envoyes): int;
}
