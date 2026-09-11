<?php

namespace App\Contracts\Repositories;

use App\Models\InvoiceSettlement;
use App\Models\Owner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Les règlements consignés : une facture réglée par mobile money, et sa référence. */
interface InvoiceSettlementRepositoryInterface
{
    /** @return Collection<int, InvoiceSettlement> indexés par identifiant de propriétaire */
    public function duMois(Carbon $debut): Collection;

    /** @return Collection<string, InvoiceSettlement> indexés par mois `AAAA-MM` */
    public function duProprietaire(Owner $owner): Collection;

    public function consigner(Owner $owner, Carbon $debut, int $montant, ?string $reference, int $adminId): void;

    /** @return int le nombre de règlements annulés */
    public function annuler(Owner $owner, Carbon $debut): int;
}
