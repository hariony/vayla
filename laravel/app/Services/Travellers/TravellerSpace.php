<?php

namespace App\Services\Travellers;

use App\Contracts\Repositories\TravellerRepositoryInterface;
use App\Data\Travellers\TravellerAccountData;
use App\Data\Travellers\TravellerAccountPageData;
use App\Data\Travellers\TravellerBookingData;
use App\Data\Travellers\TravellerBookingsPageData;
use App\Data\Travellers\TravellerIdentityData;
use App\DTOs\Auth\TravellerAccountDto;
use App\Models\Booking;
use App\Models\User;

/**
 * L'espace client : ses séjours, rattachés **par l'adresse e-mail**, et ses
 * informations. L'adresse n'a pas de champ — elle est l'identifiant de
 * connexion, et elle rattache les séjours ; l'écran dit combien en dépendent.
 */
final class TravellerSpace
{
    public function __construct(
        private TravellerRepositoryInterface $voyageurs,
    ) {}

    public function reservations(User $user): TravellerBookingsPageData
    {
        return new TravellerBookingsPageData(
            traveller: new TravellerIdentityData($user->name, $user->email),
            bookings: $this->voyageurs->reservations($user->email)->map(fn (Booking $b) => TravellerBookingData::fromModel($b))->values()->all(),
        );
    }

    public function compte(User $user): TravellerAccountPageData
    {
        return new TravellerAccountPageData(new TravellerAccountData(
            firstName: $user->first_name,
            lastName: $user->last_name,
            phone: $user->phone,
            email: $user->email,
            sejours: $this->voyageurs->nombreSejours($user->email),
        ));
    }

    public function modifier(User $user, TravellerAccountDto $compte): void
    {
        $this->voyageurs->modifierCompte($user, $compte);
    }
}
