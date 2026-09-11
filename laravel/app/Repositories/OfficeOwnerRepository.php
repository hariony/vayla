<?php

namespace App\Repositories;

use App\Contracts\Repositories\OfficeOwnerRepositoryInterface;
use App\Enums\ListingStatus;
use App\Models\Owner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OfficeOwnerRepository implements OfficeOwnerRepositoryInterface
{
    public function paginer(bool $numeroAVerifier, ?string $recherche, int $parPage): LengthAwarePaginator
    {
        return Owner::query()
            ->withCount([
                'listings',
                'listings as en_ligne_count' => fn (Builder $q) => $q->where('status', ListingStatus::Published->value),
                'listings as a_verifier_count' => fn (Builder $q) => $q->where('status', ListingStatus::Submitted->value),
                'bookings',
            ])
            ->when($numeroAVerifier, fn (Builder $q) => $q->whereNull('phone_verified_at'))
            ->when($recherche, fn (Builder $q, string $r) => $this->chercher($q, $r))
            ->orderBy('name')
            ->paginate($parPage)
            ->withQueryString();
    }

    public function total(): int
    {
        return Owner::query()->count();
    }

    public function nombreNumeroAVerifier(): int
    {
        return Owner::query()->whereNull('phone_verified_at')->count();
    }

    public function nombreAAppeler(): int
    {
        return Owner::query()
            ->whereNull('phone_verified_at')
            ->whereHas('listings', fn ($q) => $q->where('status', ListingStatus::Submitted->value))
            ->count();
    }

    public function pourFiche(Owner $owner): Owner
    {
        return $owner->load(['listings' => fn ($q) => $q->with(['destination', 'photos'])->latest('updated_at')]);
    }

    public function tousParNom(): Collection
    {
        return Owner::query()->orderBy('name')->get();
    }

    public function trouver(int $id): ?Owner
    {
        return Owner::query()->find($id);
    }

    public function marquerNumeroVerifie(Owner $owner): void
    {
        $owner->forceFill(['phone_verified_at' => Carbon::now()])->save();
    }

    private function chercher(Builder $q, string $recherche): Builder
    {
        $motif = '%'.mb_strtolower($recherche).'%';
        $chiffres = preg_replace('/\D+/', '', $recherche);

        return $q->where(fn (Builder $w) => $w
            ->whereRaw('lower(name) like ?', [$motif])
            ->orWhereRaw('lower(email) like ?', [$motif])
            ->orWhereRaw('lower(city) like ?', [$motif])
            ->when($chiffres !== '', fn (Builder $x) => $x->orWhere('phone', 'like', '%'.$chiffres.'%')));
    }
}
