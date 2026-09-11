<?php

namespace App\Data\Invoices;

use App\Models\Booking;
use Spatie\LaravelData\Data;

/**
 * Une ligne de facture : un séjour effectué, avec **le prix et le taux figés
 * à la réservation** — c'est ce qui permet au propriétaire de la vérifier.
 */
final class InvoiceLineData extends Data
{
    public function __construct(
        public readonly string $reference,
        public readonly string $listing,
        public readonly string $traveller,
        public readonly string $arrival,
        public readonly string $departure,
        public readonly int $nights,
        public readonly int $pricePerNight,
        public readonly int $total,
        public readonly float $rate,
        public readonly int $commission,
    ) {}

    public static function fromModel(Booking $b): self
    {
        return new self(
            reference: $b->reference,
            listing: $b->listing->title,
            traveller: $b->traveller,
            arrival: $b->arrival->toDateString(),
            departure: $b->departure->toDateString(),
            nights: (int) $b->nights,
            pricePerNight: (int) $b->price_per_night,
            total: (int) $b->total,
            rate: (float) $b->commission_rate,
            commission: $b->commission(),
        );
    }
}
