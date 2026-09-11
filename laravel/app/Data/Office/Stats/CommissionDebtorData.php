<?php

namespace App\Data\Office\Stats;

use Spatie\LaravelData\Data;

/** Un propriétaire qui doit encore, sur les mois clos de la période : combien, et pour quels mois. */
final class CommissionDebtorData extends Data
{
    /** @param  list<string>  $mois  libellés courts, du plus ancien au plus récent */
    public function __construct(
        public readonly ?int $ownerId,
        public readonly string $nom,
        public readonly int $reste,
        public readonly array $mois,
    ) {}
}
