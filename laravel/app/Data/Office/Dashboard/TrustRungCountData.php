<?php

namespace App\Data\Office\Dashboard;

use Spatie\LaravelData\Data;

/** Un barreau de l'échelle, et combien d'annonces en ligne s'y tiennent. */
final class TrustRungCountData extends Data
{
    public function __construct(
        public readonly int $niveau,
        public readonly string $label,
        public readonly int $nombre,
    ) {}
}
