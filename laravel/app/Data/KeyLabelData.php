<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/** Une entrée de filtre : la clé qui part dans l'adresse, le libellé qui s'affiche. */
final class KeyLabelData extends Data
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
    ) {}
}
