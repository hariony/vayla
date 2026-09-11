<?php

namespace App\Contracts\Invoices;

use App\Data\Invoices\InvoiceData;
use App\Data\Invoices\InvoiceHistoryData;
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
    public function forOwner(Owner $owner, ?Carbon $month = null): InvoiceData;

    public function historique(Owner $owner, int $mois = 6): InvoiceHistoryData;
}
