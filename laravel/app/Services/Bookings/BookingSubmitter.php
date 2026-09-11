<?php

namespace App\Services\Bookings;

use App\DTOs\Bookings\NewBookingDto;
use App\Exceptions\BookingRefusedException;
use App\Models\Booking;
use App\Services\BookingService;

/** Déposer une demande de séjour sur une annonce, retrouvée par son adresse. */
final class BookingSubmitter
{
    public function __construct(
        private BookableListings $annonces,
        private BookingService $reservations,
    ) {}

    /** @throws BookingRefusedException */
    public function soumettre(string $slug, NewBookingDto $demande): Booking
    {
        return $this->reservations->book($this->annonces->trouver($slug), $demande);
    }
}
