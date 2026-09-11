<?php

namespace App\Data\Office\Dashboard;

use Spatie\LaravelData\Data;

/** Les props de `Office/Dashboard` : ce qui attend quelqu'un, avant ce qui se mesure. */
final class DashboardPageData extends Data
{
    public function __construct(
        public readonly array $aTraiter,
        public readonly array $annonces,
        public readonly array $demandes,
        public readonly DashboardFiguresData $chiffres,
        public readonly array $echelle,
        public readonly array $activite,
    ) {}
}
