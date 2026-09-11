<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/** Un barreau de l'échelle, et le nombre de logements qui l'ont atteint — zéro compris. */
final class TrustCountData extends Data
{
    public function __construct(
        public readonly int $level,
        public readonly string $key,
        public readonly string $name,
        public readonly int $count,
    ) {}
}
