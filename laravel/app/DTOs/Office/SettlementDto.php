<?php

namespace App\DTOs\Office;

use Illuminate\Support\Carbon;

/** Le règlement d'une facture : quel propriétaire, quel mois, quelle référence mobile money. */
final readonly class SettlementDto
{
    public function __construct(
        public int $ownerId,
        public Carbon $mois,
        public ?string $reference = null,
    ) {}
}
