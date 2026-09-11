<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Un point vérifiable (photos, adresse, équipements…) : combien l'ont
 * confirmé, combien l'ont signalé, **à la même taille**. `answered` ne compte
 * que ceux qui se sont prononcés sur ce point.
 */
final class ConfirmationPointData extends Data
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $long,
        public readonly string $icon,
        public readonly int $confirmed,
        public readonly int $flagged,
        public readonly int $answered,
    ) {}
}
