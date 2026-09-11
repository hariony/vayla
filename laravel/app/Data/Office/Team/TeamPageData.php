<?php

namespace App\Data\Office\Team;

use Spatie\LaravelData\Data;

/** Les props de `Office/Team/Index`. */
final class TeamPageData extends Data
{
    public function __construct(
        public readonly array $membres,
    ) {}
}
