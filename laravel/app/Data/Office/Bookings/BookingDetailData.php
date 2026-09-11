<?php

namespace App\Data\Office\Bookings;

use App\Data\Office\BookingRowData;
use App\Data\Office\ListingRefData;
use App\Data\Office\OwnerRefData;
use App\Data\Office\PhoneData;
use App\Models\Booking;
use Spatie\LaravelData\Data;

/**
 * Une réservation, pour sa fiche : la ligne de file, plus l'argent figé à la
 * demande — prix, taux, commission — et le voyageur.
 */
final class BookingDetailData extends Data
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
        public readonly int $total,
        public readonly ?int $heuresRestantes,
        public readonly bool $nonLu,
        public readonly bool $isDemo,
        public readonly ?ListingRefData $listing,
        public readonly ?OwnerRefData $owner,
        public readonly ?string $message,
        public readonly int $pricePerNight,
        public readonly float $rate,
        public readonly int $commission,
        public readonly ?string $answeredAt,
        public readonly ?string $completedAt,
        public readonly string $createdAt,
        public readonly ?string $closedReason,
        public readonly bool $annulable,
        public readonly TravellerContactData $voyageur,
    ) {}

    public static function fromModel(Booking $b): self
    {
        return new self(...[
            ...BookingRowData::fromModel($b)->champs(),
            'message' => $b->message,
            'pricePerNight' => (int) $b->price_per_night,
            'rate' => (float) $b->commission_rate,
            'commission' => (int) $b->commission(),
            'answeredAt' => $b->answered_at?->toIso8601String(),
            'completedAt' => $b->completed_at?->toIso8601String(),
            'createdAt' => $b->created_at->toIso8601String(),
            'closedReason' => $b->closed_reason,
            'annulable' => ! $b->status->isFinal(),
            'voyageur' => new TravellerContactData((string) $b->traveller, $b->traveller_email, PhoneData::depuis($b->traveller_phone)),
        ]);
    }
}
