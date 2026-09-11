<?php

namespace App\Data\Office\WhatsApp;

use App\Data\Office\OwnerRefData;
use Spatie\LaravelData\Data;

/** Un message de la file WhatsApp, avec son lien `wa.me` prêt à cliquer. */
final class OutboundMessageRowData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $kind,
        public readonly bool $urgent,
        public readonly string $to,
        public readonly ?OwnerRefData $owner,
        public readonly ?BookingRefData $booking,
        public readonly string $body,
        public readonly string $lien,
        public readonly string $createdAt,
        public readonly ?string $sentAt,
    ) {}
}
