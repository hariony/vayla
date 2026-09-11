<?php

namespace App\Data\Owners;

use App\Enums\MessageAuthor;
use App\Models\Booking;
use Spatie\LaravelData\Data;

/**
 * Une réservation, vue par son propriétaire. `facturable` : seul un séjour
 * confirmé par le voyageur se facture — le dire ici évite la question
 * « pourquoi cette ligne n'est pas sur ma facture ». `nonLus` : un fil jamais
 * ouvert compte comme entièrement neuf.
 */
final class OwnerBookingData extends Data
{
    public function __construct(
        public readonly string $reference,
        public readonly string $listing,
        public readonly ?string $listingSlug,
        public readonly string $traveller,
        public readonly string $phone,
        public readonly int $guests,
        public readonly string $arrival,
        public readonly string $departure,
        public readonly int $nights,
        public readonly int $total,
        public readonly int $commission,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly bool $facturable,
        public readonly ?string $reason,
        public readonly int $messages,
        public readonly int $nonLus,
    ) {}

    public static function fromModel(Booking $b): self
    {
        return new self(
            reference: $b->reference,
            listing: $b->listing?->title ?? '—',
            listingSlug: $b->listing?->slug,
            traveller: $b->traveller,
            phone: $b->traveller_phone,
            guests: (int) $b->guests,
            arrival: $b->arrival->toDateString(),
            departure: $b->departure->toDateString(),
            nights: (int) $b->nights,
            total: (int) $b->total,
            commission: $b->commission(),
            status: $b->status->value,
            statusLabel: $b->status->label(),
            facturable: $b->isBillable(),
            reason: $b->closed_reason,
            messages: $b->messages->count(),
            nonLus: $b->messages
                ->where('author', '!=', MessageAuthor::Owner)
                ->filter(fn ($m) => $b->owner_read_at === null || $m->created_at->greaterThan($b->owner_read_at))
                ->count(),
        );
    }
}
