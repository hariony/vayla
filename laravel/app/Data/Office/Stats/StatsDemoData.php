<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/** `present` : il existe des données de démonstration ; `inclus` : elles sont dans les courbes. */
final class StatsDemoData extends Data
{
    public function __construct(
        public readonly bool $inclus,
        public readonly bool $present,
    ) {}
}
