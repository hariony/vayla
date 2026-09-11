<?php

namespace App\Data\Office\Content;

use Spatie\LaravelData\Data;

/** La commission des nouvelles demandes, en pour cent, et d'où elle vient. */
final class CommissionSettingData extends Data
{
    public function __construct(
        public readonly float $pourcent,
        public readonly ?SettingTraceData $trace,
        public readonly string $source,
    ) {}
}
