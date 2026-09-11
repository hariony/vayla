<?php

namespace App\Contracts\Invoices;

use App\Models\Owner;
use Illuminate\Support\Carbon;

/**
 * La facture d'un propriétaire, **recalculée depuis les séjours effectués** —
 * jamais saisie. Implémenté par `InvoiceService`, que lit aussi l'espace
 * propriétaire : les deux écrans ne peuvent pas diverger sur un montant. Les
 * tableaux sont la frontière du lot 3.
 */
interface InvoiceCalculator
{
    /** @return array<string, mixed> */
    public function forOwner(Owner $owner, ?Carbon $month = null): array;

    /** @return array{encours: array<string, mixed>, factures: array<int, array<string, mixed>>} */
    public function historique(Owner $owner, int $mois = 6): array;
}
