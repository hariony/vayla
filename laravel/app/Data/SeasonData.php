<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * La saison d'une façade climatique. `caveat` est écrit à l'écran, pas
 * seulement ici : ce sont des tendances, **pas une prévision météo**.
 */
final class SeasonData extends Data
{
    /**
     * @param  array<string, SeasonMonthData>  $months  par mois « AAAA-MM », à partir du mois en cours
     * @param  list<SeasonYearMonthData>  $year
     * @param  list<string>  $best  les noms des meilleurs mois
     */
    public function __construct(
        public readonly string $zone,
        public readonly array $months,
        public readonly array $year,
        public readonly array $best,
        public readonly string $caveat,
    ) {}
}
