<?php

namespace App\DTOs\Content;

/** Le taux de change **et sa date, saisie avec lui** — jamais déduite de `now()`. */
final readonly class EurRateDto
{
    public function __construct(
        public float $taux,
        public string $releveLe,
    ) {}
}
