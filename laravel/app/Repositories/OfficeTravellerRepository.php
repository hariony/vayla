<?php

namespace App\Repositories;

use App\Contracts\Repositories\OfficeTravellerRepositoryInterface;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OfficeTravellerRepository implements OfficeTravellerRepositoryInterface
{
    public function paginer(?string $recherche, int $parPage): LengthAwarePaginator
    {
        return User::query()
            ->select('users.*')
            // Les séjours se rattachent par l'adresse, pas par une clé
            // étrangère : c'est ce qui permet de retrouver ceux réservés avant
            // la création du compte.
            ->selectSub(Booking::query()->selectRaw('count(*)')->whereColumn('bookings.traveller_email', 'users.email'), 'bookings_count')
            ->when($recherche, fn (Builder $q, string $r) => $this->chercher($q, $r))
            ->latest('created_at')
            ->paginate($parPage)
            ->withQueryString();
    }

    public function total(): int
    {
        return User::query()->count();
    }

    private function chercher(Builder $q, string $recherche): Builder
    {
        $motif = '%'.mb_strtolower($recherche).'%';
        $chiffres = preg_replace('/\D+/', '', $recherche);

        return $q->where(fn (Builder $w) => $w
            ->whereRaw('lower(email) like ?', [$motif])
            ->orWhereRaw('lower(first_name) like ?', [$motif])
            ->orWhereRaw('lower(last_name) like ?', [$motif])
            ->when($chiffres !== '', fn (Builder $x) => $x->orWhere('phone', 'like', '%'.$chiffres.'%')));
    }
}
