<?php

namespace App\Repositories;

use App\Contracts\Repositories\OfficeBookingRepositoryInterface;
use App\Enums\BookingQueueFilter;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Owner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OfficeBookingRepository implements OfficeBookingRepositoryInterface
{
    public function nombreAuStatut(BookingStatus $statut): int
    {
        return Booking::query()->where('status', $statut->value)->count();
    }

    public function comptesParFiltre(): array
    {
        $comptes = Booking::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return collect(BookingQueueFilter::cases())
            ->filter(fn (BookingQueueFilter $f) => $f->statuts() !== null)
            ->mapWithKeys(fn (BookingQueueFilter $f) => [
                $f->value => (int) collect($f->statuts())->sum(fn (BookingStatus $s) => $comptes[$s->value] ?? 0),
            ])
            ->all();
    }

    public function paginer(BookingQueueFilter $filtre, ?string $recherche, int $parPage): LengthAwarePaginator
    {
        $statuts = $filtre->statuts();

        return Booking::query()
            ->with(['listing.owner', 'listing.destination'])
            ->when($statuts, fn (Builder $q) => $q->whereIn('status', array_map(fn (BookingStatus $s) => $s->value, $statuts)))
            ->when($recherche, fn (Builder $q, string $r) => $this->chercher($q, $r))
            // Une demande qui attend se trie par son échéance : la plus proche
            // de l'expiration d'abord. Le reste, par arrivée la plus récente.
            ->when($filtre === BookingQueueFilter::Attente,
                fn (Builder $q) => $q->orderBy('hold_expires_at'),
                fn (Builder $q) => $q->latest('arrival'))
            ->paginate($parPage)
            ->withQueryString();
    }

    public function urgentes(int $heures, int $limite): Collection
    {
        return $this->expirantSous($heures)->with(['listing.owner'])->orderBy('hold_expires_at')->limit($limite)->get();
    }

    public function nombreExpirantSous(int $heures): int
    {
        return $this->expirantSous($heures)->count();
    }

    public function nombreCreeesDepuis(Carbon $depuis): int
    {
        return Booking::query()->where('created_at', '>=', $depuis)->count();
    }

    public function nombreSejoursEffectuesDepuis(Carbon $depuis): int
    {
        return Booking::query()
            ->where('status', BookingStatus::Completed->value)
            ->where('departure', '>=', $depuis->toDateString())
            ->count();
    }

    public function dernieresDuProprietaire(Owner $owner, int $limite): Collection
    {
        return $owner->bookings()->with('listing')->latest('arrival')->limit($limite)->get();
    }

    public function pourFiche(Booking $booking): Booking
    {
        return $booking->loadMissing(['listing.owner', 'listing.destination']);
    }

    private function expirantSous(int $heures): Builder
    {
        return Booking::query()
            ->where('status', BookingStatus::Pending->value)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '>', Carbon::now())
            ->where('hold_expires_at', '<=', Carbon::now()->addHours($heures));
    }

    private function chercher(Builder $q, string $recherche): Builder
    {
        $motif = '%'.mb_strtolower($recherche).'%';
        // Sans chiffre dans la saisie, `like '%%'` ramènerait tout.
        $chiffres = preg_replace('/\D+/', '', $recherche);

        return $q->where(fn (Builder $w) => $w
            ->whereRaw('lower(reference) like ?', [$motif])
            ->orWhereRaw('lower(traveller) like ?', [$motif])
            ->orWhereRaw('lower(traveller_email) like ?', [$motif])
            ->when($chiffres !== '', fn (Builder $x) => $x->orWhere('traveller_phone', 'like', '%'.$chiffres.'%'))
            ->orWhereHas('listing', fn (Builder $l) => $l->whereRaw('lower(title) like ?', [$motif])));
    }
}
