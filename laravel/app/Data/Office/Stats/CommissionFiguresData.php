<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/**
 * Les chiffres de la commission sur la période. `reste`, `recouvrement`,
 * `factures` et `reglees` ne portent que sur les **mois clos** ; `enCours`
 * est ce qui s'accumule ce mois-ci et n'est pas encore dû.
 */
final class CommissionFiguresData extends Data
{
    public function __construct(
        public readonly int $volume,
        public readonly ?float $taux,
        public readonly int $facturee,
        public readonly int $reglee,
        public readonly int $reste,
        public readonly ?float $recouvrement,
        public readonly int $factures,
        public readonly int $reglees,
        public readonly int $enCours,
    ) {}
}
