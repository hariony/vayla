<?php

namespace App\Data\Owners;

use Spatie\LaravelData\Data;

/** Les props de `Owner/Bookings/Index` : l'historique, le filtre en cours, et les pastilles. */
final class OwnerBookingsPageData extends Data
{
    /**
     * @param  list<OwnerBookingData>  $bookings
     * @param  list<OwnerBookingCountData>  $compteurs
     */
    public function __construct(
        public readonly array $bookings,
        public readonly string $filtre,
        public readonly array $compteurs,
    ) {}
}
