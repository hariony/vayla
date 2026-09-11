<?php

namespace App\Data\Office\Listings;

use Spatie\LaravelData\Data;

/** Les règles du séjour, telles que la modération les relit. */
final class StayRulesSummaryData extends Data
{
    public function __construct(
        public readonly int $min,
        public readonly ?int $max,
        public readonly ?string $checkIn,
        public readonly ?string $checkOut,
        public readonly bool $pets,
        public readonly bool $smoking,
        public readonly bool $events,
    ) {}
}
