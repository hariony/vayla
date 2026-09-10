<?php

namespace App\Data;

use App\Models\Booking;
use Spatie\LaravelData\Data;

/**
 * Une réservation, telle que le voyageur la relit.
 *
 * `commission` n'y est pas : c'est une affaire entre Vayla et le
 * propriétaire, elle ne regarde pas le voyageur et n'a pas à figurer sur son
 * écran. Le montant qu'il voit est celui qu'il réglera sur place.
 */
class BookingData extends Data
{
    public function __construct(
        public readonly string $reference,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly string $traveller,
        public readonly int $guests,
        public readonly string $arrival,
        public readonly string $departure,
        public readonly int $nights,
        public readonly int $pricePerNight,
        public readonly int $total,
        public readonly ?string $holdExpiresAt,
        public readonly ListingData $listing,
        public readonly ?string $ownerName,
    ) {}

    public static function fromModel(Booking $booking): self
    {
        return new self(
            reference: $booking->reference,
            status: $booking->status->value,
            statusLabel: $booking->status->label(),
            traveller: $booking->traveller,
            guests: $booking->guests,
            arrival: $booking->arrival->toDateString(),
            departure: $booking->departure->toDateString(),
            nights: $booking->nights,
            pricePerNight: $booking->price_per_night,
            total: $booking->total,
            holdExpiresAt: $booking->hold_expires_at?->toIso8601String(),
            listing: ListingData::fromModel($booking->listing),
            // Prénom et nom du propriétaire seulement : son numéro lui
            // appartient, il le donne lui-même quand il accepte.
            ownerName: $booking->listing->owner?->name,
        );
    }
}
