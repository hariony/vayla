<?php

namespace App\Repositories;

use App\Contracts\Repositories\OfficeStatsRepositoryInterface;
use App\Enums\BookingStatus;
use App\Enums\ListingStatus;
use App\Models\Booking;
use App\Models\InvoiceSettlement;
use App\Models\Listing;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OfficeStatsRepository implements OfficeStatsRepositoryInterface
{
    public function demandesDepuis(Carbon $debut, bool $avecDemo): Collection
    {
        return $this->reel(Booking::query(), $avecDemo)
            ->with('listing.destination')
            ->where('created_at', '>=', $debut)
            ->get();
    }

    public function sejoursDepuis(Carbon $debut, bool $avecDemo): Collection
    {
        return $this->reel(Booking::query(), $avecDemo)
            ->with('listing.owner')
            ->where('status', BookingStatus::Completed->value)
            ->where('departure', '>=', $debut->toDateString())
            ->get();
    }

    public function reglementsDepuis(Carbon $debut, bool $avecDemo): Collection
    {
        return InvoiceSettlement::query()
            ->where('month', '>=', $debut->toDateString())
            ->when(! $avecDemo, fn ($q) => $q->whereHas('owner', fn ($o) => $o->where('is_demo', false)))
            ->get();
    }

    public function inscriptionsVoyageurs(Carbon $debut, bool $avecDemo): Collection
    {
        return User::query()
            ->where('created_at', '>=', $debut)
            ->when(! $avecDemo, fn ($q) => $q->where('email', 'not like', '%demo.vayla.test'))
            ->pluck('created_at');
    }

    public function inscriptionsProprietaires(Carbon $debut, bool $avecDemo): Collection
    {
        return $this->reel(Owner::query(), $avecDemo)->where('created_at', '>=', $debut)->pluck('created_at');
    }

    public function annoncesCreees(Carbon $debut, bool $avecDemo): Collection
    {
        return $this->reel(Listing::query(), $avecDemo)->where('created_at', '>=', $debut)->pluck('created_at');
    }

    public function annoncesParStatut(bool $avecDemo): array
    {
        return $this->compterPar($this->reel(Listing::query(), $avecDemo), 'status');
    }

    public function publieesParNiveau(bool $avecDemo): array
    {
        $enLigne = $this->reel(Listing::query(), $avecDemo)->where('status', ListingStatus::Published->value);

        return $this->compterPar($enLigne, 'trust_level');
    }

    public function demoPresente(): bool
    {
        return Booking::query()->where('is_demo', true)->exists();
    }

    /** Sans la démonstration quand on la retire. */
    private function reel(Builder $requete, bool $avecDemo): Builder
    {
        return $requete->when(! $avecDemo, fn ($q) => $q->where('is_demo', false));
    }

    /** @return array<string|int, int> valeur de la colonne → nombre de lignes */
    private function compterPar(Builder $requete, string $colonne): array
    {
        return $requete->toBase()
            ->select($colonne)->selectRaw('count(*) as n')
            ->groupBy($colonne)
            ->pluck('n', $colonne)
            ->map(fn ($n) => (int) $n)
            ->all();
    }
}
