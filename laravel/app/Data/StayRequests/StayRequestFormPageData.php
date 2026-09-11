<?php

namespace App\Data\StayRequests;

use App\Data\OptionData;
use Spatie\LaravelData\Data;

/** Les props de `Demande/Create`. */
final class StayRequestFormPageData extends Data
{
    /** @param  array<int, OptionData>  $destinations */
    public function __construct(
        public readonly array $destinations,
        public readonly StayRequestInitialData $initial,
        public readonly ?SentStayRequestData $envoyee,
    ) {}
}
