<?php

namespace App\Data\Office\Content;

use Spatie\LaravelData\Data;

/** Le taux de change en vigueur, sa date, et d'où il vient (`back-office` ou `configuration`). */
final class EurRateSettingData extends Data
{
    public function __construct(
        public readonly float $valeur,
        public readonly string $date,
        public readonly ?SettingTraceData $trace,
        public readonly string $source,
    ) {}
}
