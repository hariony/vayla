<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/**
 * Un mois de commission. `reste` est nul pour le mois en cours : ce n'est pas
 * encore une facture, rien n'y est dû. `taux` : la commission rapportée au
 * volume des séjours — chaque réservation ayant figé le sien.
 */
final class CommissionMonthData extends Data
{
    public function __construct(
        public readonly string $mois,
        public readonly string $label,
        public readonly bool $enCours,
        public readonly int $sejours,
        public readonly int $volume,
        public readonly ?float $taux,
        public readonly int $facturee,
        public readonly int $reglee,
        public readonly ?int $reste,
        public readonly int $factures,
        public readonly int $reglees,
    ) {}
}
