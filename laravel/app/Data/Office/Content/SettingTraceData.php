<?php

namespace App\Data\Office\Content;

use Spatie\LaravelData\Data;

/** Quand un réglage a été changé, et par qui. */
final class SettingTraceData extends Data
{
    public function __construct(
        public readonly ?string $at,
        public readonly ?string $par,
    ) {}
}
