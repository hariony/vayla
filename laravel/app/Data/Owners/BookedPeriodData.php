<?php

namespace App\Data\Owners;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Spatie\LaravelData\Data;

/**
 * Des nuits prises par une réservation. **Elles ne se libèrent pas d'ici** :
 * un « Libérer » posé dessus ferait disparaître un séjour sans que le voyageur
 * l'apprenne. La liste dit où aller — refuser la demande, annuler la réservation.
 */
final class BookedPeriodData extends Data
{
    public function __construct(
        public readonly string $reference,
        public readonly string $traveller,
        public readonly string $from,
        public readonly string $to,
        public readonly int $nights,
        public readonly bool $pending,
    ) {}

    public static function fromModel(Booking $b): self
    {
        return new self(
            reference: $b->reference,
            traveller: $b->traveller,
            from: $b->arrival->toDateString(),
            to: $b->departure->copy()->subDay()->toDateString(),
            nights: (int) $b->nights,
            pending: $b->status === BookingStatus::Pending,
        );
    }
}
