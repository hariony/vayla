<?php

namespace App\Data\Pages;

use App\DTOs\Bookings\BookingPrefillDto;
use Spatie\LaravelData\Data;

/** Les dates et le nombre de voyageurs choisis sur la fiche, pour pré-remplir le formulaire. */
final class BookingPrefillData extends Data
{
    public function __construct(
        public readonly ?string $arrival,
        public readonly ?string $departure,
        public readonly ?int $guests,
    ) {}

    public static function depuis(BookingPrefillDto $p): self
    {
        return new self($p->arrival, $p->departure, $p->guests);
    }
}
