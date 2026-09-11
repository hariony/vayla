<?php

namespace App\Repositories;

use App\Contracts\Repositories\OfficeListingRepositoryInterface;
use App\Enums\BookingStatus;
use App\Enums\ListingStatus;
use App\Enums\TrustLevel;
use App\Models\Listing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OfficeListingRepository implements OfficeListingRepositoryInterface
{
    public function nombreAuStatut(ListingStatus $statut): int
    {
        return Listing::query()->where('status', $statut->value)->count();
    }

    public function comptesParStatut(): array
    {
        $comptes = Listing::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return collect(ListingStatus::cases())
            ->mapWithKeys(fn (ListingStatus $s) => [$s->value => (int) ($comptes[$s->value] ?? 0)])
            ->all();
    }

    public function paginer(?ListingStatus $statut, ?string $recherche, int $parPage): LengthAwarePaginator
    {
        return Listing::query()
            ->with(['destination', 'owner', 'photos'])
            ->withCount('confirmations')
            ->when($statut, fn (Builder $q) => $q->where('status', $statut->value))
            ->when($recherche, fn (Builder $q, string $r) => $this->chercher($q, $r))
            // La plus ancienne demande de vérification d'abord : c'est celle
            // dont le propriétaire attend l'appel depuis le plus longtemps.
            ->when($statut === ListingStatus::Submitted, fn (Builder $q) => $q->orderBy('updated_at'), fn (Builder $q) => $q->latest('updated_at'))
            ->paginate($parPage)
            ->withQueryString();
    }

    public function echelleEnLigne(): array
    {
        $comptes = Listing::query()
            ->where('status', ListingStatus::Published->value)
            ->selectRaw('trust_level, count(*) as n')
            ->groupBy('trust_level')
            ->pluck('n', 'trust_level');

        return collect(TrustLevel::cases())->mapWithKeys(fn (TrustLevel $n) => [$n->value => (int) ($comptes[$n->value] ?? 0)])->all();
    }

    public function aVerifier(int $limite): Collection
    {
        return Listing::query()
            ->with(['destination', 'owner', 'photos'])
            ->where('status', ListingStatus::Submitted->value)
            ->orderBy('updated_at')
            ->limit($limite)
            ->get();
    }

    public function pourModeration(Listing $listing): Listing
    {
        return $listing->loadMissing(['destination', 'owner', 'photos', 'amenities'])->loadCount('confirmations');
    }

    public function nombreAVenir(Listing $listing): int
    {
        return $listing->bookings()
            ->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Accepted->value])
            ->where('departure', '>=', Carbon::today()->toDateString())
            ->count();
    }

    public function nombreConfirmations(Listing $listing): int
    {
        return $listing->confirmations_count ?? $listing->confirmations()->count();
    }

    public function publier(Listing $listing): void
    {
        $listing->update(['status' => ListingStatus::Published, 'review_note' => null]);
    }

    public function renvoyer(Listing $listing, string $motif): void
    {
        $listing->update(['status' => ListingStatus::Draft, 'review_note' => $motif]);
    }

    public function archiver(Listing $listing): void
    {
        $listing->update(['status' => ListingStatus::Archived]);
    }

    public function changerNiveau(Listing $listing, TrustLevel $niveau): void
    {
        $listing->update(['trust_level' => $niveau]);
    }

    private function chercher(Builder $q, string $recherche): Builder
    {
        $motif = '%'.mb_strtolower($recherche).'%';

        return $q->where(fn (Builder $w) => $w
            ->whereRaw('lower(title) like ?', [$motif])
            ->orWhereRaw('lower(slug) like ?', [$motif])
            ->orWhereHas('owner', fn (Builder $o) => $o->whereRaw('lower(name) like ?', [$motif]))
            ->orWhereHas('destination', fn (Builder $d) => $d->whereRaw('lower(name) like ?', [$motif])));
    }
}
