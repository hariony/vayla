<?php

namespace App\Data\Travellers;

use App\Models\Booking;
use Spatie\LaravelData\Data;

/** Un séjour dans « Mes réservations ». */
final class TravellerBookingData extends Data
{
    public function __construct(
        public readonly string $reference,
        public readonly ?string $listing,
        public readonly ?string $slug,
        public readonly ?string $place,
        public readonly string $arrival,
        public readonly string $departure,
        public readonly int $nights,
        public readonly int $guests,
        public readonly int $total,
        public readonly string $status,
        public readonly string $statusLabel,
    ) {}

    public static function fromModel(Booking $b): self
    {
        return new self(
            reference: $b->reference,
            listing: $b->listing?->title,
            slug: $b->listing?->slug,
            place: $b->listing?->destination?->name,
            arrival: $b->arrival->toDateString(),
            departure: $b->departure->toDateString(),
            nights: (int) $b->nights,
            guests: (int) $b->guests,
            total: (int) $b->total,
            status: $b->status->value,
            statusLabel: $b->status->label(),
        );
    }
}
