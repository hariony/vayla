<?php

namespace App\Data\Shared;

use Spatie\LaravelData\Data;

/** Un fournisseur de connexion proposé à l'écran — seulement ceux qui sont configurés. */
final class SocialOptionData extends Data
{
    public function __construct(
        public readonly string $cle,
        public readonly string $label,
    ) {}
}
