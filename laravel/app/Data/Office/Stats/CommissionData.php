<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/**
 * Le bloc « Commission » : ses chiffres, ses deux courbes, le détail des mois
 * — le plus récent en tête, les mois sans séjour ni règlement retirés — et
 * ceux qui doivent encore.
 */
final class CommissionData extends Data
{
    /**
     * @param  list<SeriesData>  $series  facturée et reçue, du plus ancien au mois en cours
     * @param  list<CommissionMonthData>  $mois
     * @param  list<CommissionDebtorData>  $debiteurs
     */
    public function __construct(
        public readonly CommissionFiguresData $chiffres,
        public readonly array $series,
        public readonly array $mois,
        public readonly int $moisSansActivite,
        public readonly array $debiteurs,
        public readonly int $autresDebiteurs,
    ) {}
}
