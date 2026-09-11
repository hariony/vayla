<?php

namespace App\Data\Office\Dashboard;

use Spatie\LaravelData\Data;

/** Des comptes — aucune moyenne, aucune « tendance » qui se lirait comme une promesse. */
final class DashboardFiguresData extends Data
{
    public function __construct(
        public readonly int $enLigne,
        public readonly int $proprietaires,
        public readonly int $voyageurs,
        public readonly int $reservationsMois,
        public readonly int $sejoursMois,
        public readonly int $commissionEncours,
    ) {}
}
