<?php

namespace App\Data\Owners;

use App\Models\Booking;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

/**
 * Une demande à répondre ou un séjour à venir, sur le tableau de bord. **Le
 * temps restant s'écrit en heures** (`hoursLeft`) : « il vous reste 41 h » se
 * comprend sans calcul. **La commission s'affiche à côté du total, avant la
 * réponse** — la découvrir sur la facture, c'est se sentir piégé.
 */
final class DashboardBookingData extends Data
{
    public function __construct(
        public readonly string $reference,
        public readonly string $listing,
        public readonly string $traveller,
        public readonly string $arrival,
        public readonly string $departure,
        public readonly int $nights,
        public readonly int $total,
        public readonly int $commission,
        public readonly float $rate,
        public readonly int $guests,
        public readonly string $phone,
        public readonly ?int $hoursLeft = null,
        public readonly ?string $message = null,
    ) {}

    public static function fromModel(Booking $b, bool $avecDelai = false): self
    {
        return new self(
            reference: $b->reference,
            listing: $b->listing?->title ?? '—',
            traveller: $b->traveller,
            arrival: $b->arrival->toDateString(),
            departure: $b->departure->toDateString(),
            nights: (int) $b->nights,
            total: (int) $b->total,
            commission: $b->commission(),
            rate: (float) $b->commission_rate,
            guests: (int) $b->guests,
            phone: $b->traveller_phone,
            hoursLeft: $avecDelai && $b->hold_expires_at
                ? max(0, (int) floor(Carbon::now()->diffInHours($b->hold_expires_at, false)))
                : null,
            message: $avecDelai ? $b->message : null,
        );
    }
}
