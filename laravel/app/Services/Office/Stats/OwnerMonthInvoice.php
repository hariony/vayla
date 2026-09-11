<?php

namespace App\Services\Office\Stats;

/**
 * La facture d'un propriétaire pour un mois, telle que les statistiques la
 * reconstituent : la commission des séjours partis ce mois-là, et ce qui a
 * été consigné comme réglé. Valeur interne à `CommissionStats`.
 */
final readonly class OwnerMonthInvoice
{
    public function __construct(
        public ?int $ownerId,
        public string $nom,
        public string $mois,
        public int $facturee,
        public int $recu,
        public bool $reglee,
    ) {}

    /**
     * Ce qui manque. Une facture réglée peut encore manquer de quelque chose :
     * un séjour confirmé **après** le règlement s'ajoute à son mois.
     */
    public function reste(): int
    {
        return max(0, $this->facturee - $this->recu);
    }
}
