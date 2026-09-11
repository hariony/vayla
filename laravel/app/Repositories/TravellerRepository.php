<?php

namespace App\Repositories;

use App\Contracts\Repositories\TravellerRepositoryInterface;
use App\DTOs\Auth\TravellerAccountDto;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TravellerRepository implements TravellerRepositoryInterface
{
    public function parEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function creer(string $email): User
    {
        return User::create(['email' => $email]);
    }

    public function marquerEmailVerifie(User $user): void
    {
        $user->forceFill(['email_verified_at' => Carbon::now()])->save();
    }

    public function modifierCompte(User $user, TravellerAccountDto $compte): void
    {
        $user->fill(['first_name' => $compte->firstName, 'last_name' => $compte->lastName, 'phone' => $compte->phone])->save();
    }

    public function reservations(string $email): Collection
    {
        return Booking::query()->with('listing.destination')->where('traveller_email', $email)->orderByDesc('arrival')->get();
    }

    public function nombreSejours(string $email): int
    {
        return Booking::query()->where('traveller_email', $email)->count();
    }
}
