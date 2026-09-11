<?php

namespace App\Contracts\Bookings;

use App\Exceptions\BookingRefusedException;
use App\Models\Booking;

/** Annuler une réservation : les nuits reviennent au calendrier. Implémenté par `BookingService`. */
interface BookingCancellation
{
    /** @throws BookingRefusedException si elle ne s'annule plus */
    public function cancel(Booking $booking, ?string $reason = null): Booking;
}
