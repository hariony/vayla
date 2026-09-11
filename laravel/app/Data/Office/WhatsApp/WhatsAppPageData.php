<?php

namespace App\Data\Office\WhatsApp;

use App\Data\Office\ListFilterData;
use App\Data\Office\PaginatedData;
use Spatie\LaravelData\Data;

/** Les props de `Office/WhatsApp/Index`. */
final class WhatsAppPageData extends Data
{
    public function __construct(
        public readonly PaginatedData $messages,
        public readonly array $onglets,
        public readonly ListFilterData $filtre,
    ) {}
}
