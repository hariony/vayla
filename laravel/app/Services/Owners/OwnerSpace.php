<?php

namespace App\Services\Owners;

use App\Contracts\Repositories\OwnerSpaceRepositoryInterface;
use App\Exceptions\BookingNotFoundException;
use App\Exceptions\ListingNotFoundException;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Owner;

/**
 * La portée de l'espace propriétaire : une réservation, un logement — **les
 * siens**, sinon introuvable. Une référence ou une adresse qui existe chez un
 * confrère répond exactement comme une qui n'existe pas : dire « ce n'est pas
 * à vous » confirmerait qu'elle existe.
 */
final class OwnerSpace
{
    public function __construct(
        private OwnerSpaceRepositoryInterface $espace,
    ) {}

    /** @throws BookingNotFoundException */
    public function reservation(Owner $owner, string $reference): Booking
    {
        return $this->espace->reservation($owner, $reference) ?? throw new BookingNotFoundException($reference);
    }

    /** @throws ListingNotFoundException */
    public function logement(Owner $owner, string $slug): Listing
    {
        return $this->espace->annonce($owner, $slug) ?? throw new ListingNotFoundException($slug);
    }
}
