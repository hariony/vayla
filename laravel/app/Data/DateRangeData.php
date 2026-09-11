<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Une période occupée : de la nuit `from` à la nuit `to`, **toutes deux
 * comprises** (`AAAA-MM-JJ`). `to` est la dernière nuit prise, jamais la date
 * de départ — voir `SejourData::derniereNuit()`.
 */
final class DateRangeData extends Data
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
    ) {}
}
