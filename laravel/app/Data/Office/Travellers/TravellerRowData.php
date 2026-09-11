<?php

namespace App\Data\Office\Travellers;

use App\Data\Office\PhoneData;
use Spatie\LaravelData\Data;

/** Un compte voyageur, et combien de séjours s'y rattachent par l'adresse. */
final class TravellerRowData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly string $email,
        public readonly ?PhoneData $telephone,
        public readonly int $bookings,
        public readonly ?string $createdAt,
    ) {}
}
