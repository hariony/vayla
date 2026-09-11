<?php

namespace App\Repositories;

use App\Enums\AdminActionKind;
use App\Enums\BookingStatus;
use App\Enums\ListingStatus;
use App\Enums\NotificationKind;
use App\Models\AdminAction;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\OutboundMessage;
use App\Models\Owner;
use App\Models\User;
use App\Repositories\Contracts\OfficeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OfficeRepository implements OfficeRepositoryInterface
{
    /** Les filtres de réservation, dans l'ordre des onglets. */
    public const FILTRES_RESERVATIONS = [
        'attente' => [BookingStatus::Pending],
        'acceptees' => [BookingStatus::Accepted],
        'effectuees' => [BookingStatus::Completed],
        'closes' => [BookingStatus::Declined, BookingStatus::Expired, BookingStatus::Cancelled],
    ];

    public function comptesAnnonces(): array
    {
        $comptes = Listing::query()
            ->selectRaw('status, count(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status');

        return collect(ListingStatus::cases())
            ->mapWithKeys(fn (ListingStatus $s) => [$s->value => (int) ($comptes[$s->value] ?? 0)])
            ->all();
    }

    public function annonces(?ListingStatus $statut, ?string $recherche, int $parPage = 25): LengthAwarePaginator
    {
        return Listing::query()
            ->with(['destination', 'owner', 'photos'])
            ->withCount('confirmations')
            ->when($statut, fn (Builder $q) => $q->where('status', $statut->value))
            ->when($recherche, function (Builder $q, string $r) {
                $motif = '%'.mb_strtolower($r).'%';
                $q->where(fn (Builder $w) => $w
                    ->whereRaw('lower(title) like ?', [$motif])
                    ->orWhereRaw('lower(slug) like ?', [$motif])
                    ->orWhereHas('owner', fn (Builder $o) => $o->whereRaw('lower(name) like ?', [$motif]))
                    ->orWhereHas('destination', fn (Builder $d) => $d->whereRaw('lower(name) like ?', [$motif])));
            })
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

        return collect([1, 2, 3, 4])->mapWithKeys(fn (int $n) => [$n => (int) ($comptes[$n] ?? 0)])->all();
    }

    public function annoncesAVerifier(int $limite): Collection
    {
        return Listing::query()
            ->with(['destination', 'owner', 'photos'])
            ->where('status', ListingStatus::Submitted->value)
            ->orderBy('updated_at')
            ->limit($limite)
            ->get();
    }

    public function comptesReservations(): array
    {
        $comptes = Booking::query()
            ->selectRaw('status, count(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status');

        return collect(self::FILTRES_RESERVATIONS)
            ->map(fn (array $statuts) => (int) collect($statuts)->sum(fn (BookingStatus $s) => $comptes[$s->value] ?? 0))
            ->all();
    }

    public function reservations(string $filtre, ?string $recherche, int $parPage = 25): LengthAwarePaginator
    {
        $statuts = self::FILTRES_RESERVATIONS[$filtre] ?? null;

        return Booking::query()
            ->with(['listing.owner', 'listing.destination'])
            ->when($statuts, fn (Builder $q) => $q->whereIn('status', array_map(fn (BookingStatus $s) => $s->value, $statuts)))
            ->when($recherche, function (Builder $q, string $r) {
                $motif = '%'.mb_strtolower($r).'%';
                // Sans chiffre dans la saisie, `like '%%'` ramènerait tout.
                $chiffres = preg_replace('/\D+/', '', $r);
                $q->where(fn (Builder $w) => $w
                    ->whereRaw('lower(reference) like ?', [$motif])
                    ->orWhereRaw('lower(traveller) like ?', [$motif])
                    ->orWhereRaw('lower(traveller_email) like ?', [$motif])
                    ->when($chiffres !== '', fn (Builder $x) => $x->orWhere('traveller_phone', 'like', '%'.$chiffres.'%'))
                    ->orWhereHas('listing', fn (Builder $l) => $l->whereRaw('lower(title) like ?', [$motif])));
            })
            // Une demande qui attend se trie par son échéance : la plus proche
            // de l'expiration d'abord. Le reste, par arrivée la plus récente.
            ->when($filtre === 'attente',
                fn (Builder $q) => $q->orderBy('hold_expires_at'),
                fn (Builder $q) => $q->latest('arrival'))
            ->paginate($parPage)
            ->withQueryString();
    }

    public function demandesUrgentes(int $heures, int $limite): Collection
    {
        return Booking::query()
            ->with(['listing.owner'])
            ->where('status', BookingStatus::Pending->value)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '>', Carbon::now())
            ->where('hold_expires_at', '<=', Carbon::now()->addHours($heures))
            ->orderBy('hold_expires_at')
            ->limit($limite)
            ->get();
    }

    public function proprietaires(?string $filtre, ?string $recherche, int $parPage = 25): LengthAwarePaginator
    {
        return Owner::query()
            ->withCount([
                'listings',
                'listings as en_ligne_count' => fn (Builder $q) => $q->where('status', ListingStatus::Published->value),
                'listings as a_verifier_count' => fn (Builder $q) => $q->where('status', ListingStatus::Submitted->value),
                'bookings',
            ])
            ->when($filtre === 'a-verifier', fn (Builder $q) => $q->whereNull('phone_verified_at'))
            ->when($recherche, function (Builder $q, string $r) {
                $motif = '%'.mb_strtolower($r).'%';
                $chiffres = preg_replace('/\D+/', '', $r);
                $q->where(fn (Builder $w) => $w
                    ->whereRaw('lower(name) like ?', [$motif])
                    ->orWhereRaw('lower(email) like ?', [$motif])
                    ->orWhereRaw('lower(city) like ?', [$motif])
                    ->when($chiffres !== '', fn (Builder $x) => $x->orWhere('phone', 'like', '%'.$chiffres.'%')));
            })
            ->orderBy('name')
            ->paginate($parPage)
            ->withQueryString();
    }

    public function voyageurs(?string $recherche, int $parPage = 30): LengthAwarePaginator
    {
        return User::query()
            ->select('users.*')
            // Les séjours se rattachent par l'adresse, pas par une clé
            // étrangère : c'est ce qui permet de retrouver ceux réservés avant
            // la création du compte.
            ->selectSub(
                Booking::query()->selectRaw('count(*)')->whereColumn('bookings.traveller_email', 'users.email'),
                'bookings_count',
            )
            ->when($recherche, function (Builder $q, string $r) {
                $motif = '%'.mb_strtolower($r).'%';
                $q->where(fn (Builder $w) => $w
                    ->whereRaw('lower(email) like ?', [$motif])
                    ->orWhereRaw('lower(first_name) like ?', [$motif])
                    ->orWhereRaw('lower(last_name) like ?', [$motif])
                    ->when(preg_replace('/\D+/', '', $r) !== '', fn (Builder $x) => $x->orWhere('phone', 'like', '%'.preg_replace('/\D+/', '', $r).'%')));
            })
            ->latest('created_at')
            ->paginate($parPage)
            ->withQueryString();
    }

    public function messagesWhatsApp(bool $envoyes, int $parPage = 30): LengthAwarePaginator
    {
        $urgents = collect(NotificationKind::cases())
            ->filter(fn (NotificationKind $k) => $k->urgent())
            ->map(fn (NotificationKind $k) => "'{$k->value}'")
            ->implode(',');

        return OutboundMessage::query()
            ->with(['owner', 'booking'])
            ->when($envoyes,
                fn (Builder $q) => $q->whereNotNull('sent_at')->latest('sent_at'),
                // Même ordre que la commande : l'urgent devant, puis le plus
                // ancien. Celui qui envoie à la main n'a pas le temps de trier.
                fn (Builder $q) => $q->whereNull('sent_at')
                    ->orderByRaw("case when kind in ({$urgents}) then 0 else 1 end")
                    ->orderBy('created_at'))
            ->paginate($parPage)
            ->withQueryString();
    }

    public function journal(?string $famille, int $parPage = 40): LengthAwarePaginator
    {
        $kinds = $famille
            ? collect(AdminActionKind::cases())->filter(fn (AdminActionKind $k) => $k->famille() === $famille)->map->value->values()->all()
            : null;

        return AdminAction::query()
            ->when($kinds !== null, fn (Builder $q) => $q->whereIn('kind', $kinds))
            ->latest('id')
            ->paginate($parPage)
            ->withQueryString();
    }
}
