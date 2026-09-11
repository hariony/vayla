<?php

namespace App\Data\Office;

use Spatie\LaravelData\Data;

/** Le filtre en cours d'une liste : l'onglet actif et la recherche, renvoyés à l'écran. */
final class ListFilterData extends Data
{
    public function __construct(
        public readonly string $onglet,
        public readonly string $q,
    ) {}
}
