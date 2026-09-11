<?php

namespace App\Data\Office\Bookings;

use App\Data\Office\PhoneData;
use Spatie\LaravelData\Data;

/** Le voyageur d'une réservation, et comment le joindre. */
final class TravellerContactData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?PhoneData $telephone,
    ) {}
}
