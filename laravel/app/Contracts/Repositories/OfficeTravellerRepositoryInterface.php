<?php

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Les comptes voyageurs, et combien de séjours s'y rattachent par l'adresse. */
interface OfficeTravellerRepositoryInterface
{
    /** @return LengthAwarePaginator<int, User> avec `bookings_count` */
    public function paginer(?string $recherche, int $parPage): LengthAwarePaginator;

    public function total(): int;
}
