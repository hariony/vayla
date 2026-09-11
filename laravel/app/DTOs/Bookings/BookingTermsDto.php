<?php

namespace App\DTOs\Bookings;

use Illuminate\Support\Carbon;

/**
 * Ce que Vayla fige au moment de la demande : le prix de la nuit, le total, le
 * taux de commission et l'expiration. **Figés, ils ne bougent plus** — une
 * facture qui change après coup est une facture qu'on ne paie pas.
 */
final readonly class BookingTermsDto
{
    public function __construct(
        public string $reference,
        public int $nights,
        public int $pricePerNight,
        public int $total,
        public float $commissionRate,
        public Carbon $holdExpiresAt,
    ) {}
}
