<?php

namespace App\Data\Office\WhatsApp;

use Spatie\LaravelData\Data;

/** Une réservation nommée par sa référence. */
final class BookingRefData extends Data
{
    public function __construct(
        public readonly string $reference,
    ) {}
}
