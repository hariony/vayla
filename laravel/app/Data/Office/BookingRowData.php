<?php

namespace App\Data\Office;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

/**
 * Une réservation dans une file du back-office. `heuresRestantes` : ce qu'il
 * reste à une demande avant d'expirer — **en heures**, « il reste 41 h » se
 * comprend sans calcul.
 */
final class BookingRowData extends Data
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
    ) {}

    public static function fromModel(Booking $b): self
    {
        $attente = $b->status === BookingStatus::Pending;

        return new self(
            reference: $b->reference,
            status: $b->status->value,
            statusLabel: $b->status->label(),
            traveller: (string) $b->traveller,
            guests: (int) $b->guests,
            arrival: $b->arrival->toDateString(),
            departure: $b->departure->toDateString(),
            nights: (int) $b->nights,
            total: (int) $b->total,
            heuresRestantes: $attente && $b->hold_expires_at
                ? max(0, (int) floor(Carbon::now()->diffInMinutes($b->hold_expires_at, false) / 60))
                : null,
            nonLu: $b->owner_read_at === null && $attente,
            isDemo: (bool) $b->is_demo,
            listing: $b->listing ? new ListingRefData($b->listing->id, $b->listing->title, $b->listing->slug) : null,
            owner: $b->listing?->owner ? new OwnerRefData($b->listing->owner->id, $b->listing->owner->name) : null,
        );
    }

    /**
     * Les champs, par nom — pour qu'une fiche détaillée les reprenne tels quels
     * et n'ajoute que ce qui lui est propre.
     *
     * @return array<string, mixed>
     */
    public function champs(): array
    {
        return [
            'reference' => $this->reference,
            'status' => $this->status,
            'statusLabel' => $this->statusLabel,
            'traveller' => $this->traveller,
            'guests' => $this->guests,
            'arrival' => $this->arrival,
            'departure' => $this->departure,
            'nights' => $this->nights,
            'total' => $this->total,
            'heuresRestantes' => $this->heuresRestantes,
            'nonLu' => $this->nonLu,
            'isDemo' => $this->isDemo,
            'listing' => $this->listing,
            'owner' => $this->owner,
        ];
    }
}
