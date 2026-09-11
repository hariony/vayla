<?php

namespace App\Repositories;

use App\Contracts\Repositories\InvoiceSettlementRepositoryInterface;
use App\Models\InvoiceSettlement;
use App\Models\Owner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * `month` est une chaîne `AAAA-MM-JJ`, **sans cast date** : casté, Eloquent
 * l'écrivait `2026-08-01 00:00:00` et l'égalité ne le retrouvait plus sous
 * SQLite (voir « Pièges »).
 */
class InvoiceSettlementRepository implements InvoiceSettlementRepositoryInterface
{
    public function duMois(Carbon $debut): Collection
    {
        return InvoiceSettlement::query()->where('month', $debut->toDateString())->get()->keyBy('owner_id');
    }

    public function duProprietaire(Owner $owner): Collection
    {
        return InvoiceSettlement::query()->where('owner_id', $owner->id)->get()
            ->keyBy(fn (InvoiceSettlement $s) => substr((string) $s->month, 0, 7));
    }

    public function consigner(Owner $owner, Carbon $debut, int $montant, ?string $reference, int $adminId): void
    {
        InvoiceSettlement::updateOrCreate(
            ['owner_id' => $owner->id, 'month' => $debut->toDateString()],
            ['amount' => $montant, 'reference' => $reference, 'admin_id' => $adminId, 'settled_at' => Carbon::now()],
        );
    }

    public function annuler(Owner $owner, Carbon $debut): int
    {
        return InvoiceSettlement::query()->where('owner_id', $owner->id)->where('month', $debut->toDateString())->delete();
    }
}
