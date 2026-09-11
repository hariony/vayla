<?php

namespace App\Services\Office\Bookings;

use App\Contracts\Bookings\BookingThread;
use App\Contracts\Office\JournalReader;
use App\Contracts\Repositories\OfficeBookingRepositoryInterface;
use App\Data\Office\Bookings\BookingDetailData;
use App\Data\Office\Bookings\BookingDetailPageData;
use App\Data\Office\OwnerCardData;
use App\Enums\MessageAuthor;
use App\Models\Booking;

/** La fiche d'une réservation, avec son fil. */
final class BookingDetailQuery
{
    public function __construct(
        private OfficeBookingRepositoryInterface $reservations,
        private BookingThread $fil,
        private JournalReader $journal,
    ) {}

    public function page(Booking $booking): BookingDetailPageData
    {
        $booking = $this->reservations->pourFiche($booking);
        $owner = $booking->listing?->owner;

        return new BookingDetailPageData(
            reservation: BookingDetailData::fromModel($booking),
            proprietaire: $owner ? OwnerCardData::fromModel($owner) : null,
            // Vayla lit sans marquer comme lu : ouvrir le fil depuis le
            // back-office ne doit pas faire croire au propriétaire qu'il a
            // répondu à tout.
            messages: $this->fil->fil($booking, MessageAuthor::Vayla),
            journal: $this->journal->pour($booking),
        );
    }
}
