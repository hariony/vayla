<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/** Ce qui s'est ouvert sur la période : comptes voyageurs, propriétaires, annonces saisies. */
final class CatalogueFiguresData extends Data
{
    public function __construct(
        public readonly int $voyageurs,
        public readonly int $proprietaires,
        public readonly int $annonces,
    ) {}
}
