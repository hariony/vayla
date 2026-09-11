<?php

namespace App\Data\Office\Listings;

use Spatie\LaravelData\Data;

/** Un barreau de l'échelle, pour la modération : chaque barreau fermé écrit sa raison avant qu'on clique. */
final class TrustRungChoiceData extends Data
{
    public function __construct(
        public readonly int $niveau,
        public readonly string $label,
        public readonly string $summary,
        public readonly bool $actuel,
        public readonly ?string $raison,
    ) {}
}
