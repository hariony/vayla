<?php

namespace App\Services\Office\Bookings;

use App\Contracts\Bookings\BookingCancellation;
use App\Contracts\Bookings\BookingThread;
use App\Contracts\Office\ActionJournal;
use App\Enums\AdminActionKind;
use App\Enums\MessageAuthor;
use App\Exceptions\BookingRefusedException;
use App\Exceptions\OfficeRefusal;
use App\Models\Admin;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

/**
 * Vayla intervient dans une réservation — **toujours dans le fil**, lu par les
 * deux parties : une intervention qui arriverait par un canal séparé laisserait
 * l'une des deux dans l'ignorance.
 */
final class BookingInterventions
{
    public function __construct(
        private BookingCancellation $annulation,
        private BookingThread $fil,
        private ActionJournal $journal,
    ) {}

    /** Annuler, c'est aussi **l'écrire dans le fil**, au nom de Vayla. */
    public function annuler(Admin $admin, Booking $booking, string $motif): void
    {
        try {
            DB::transaction(function () use ($booking, $motif) {
                $this->annulation->cancel($booking, $motif);
                $this->fil->ecrire($booking, MessageAuthor::Vayla, 'Vayla a annulé cette réservation. '.$motif);
            });
        } catch (BookingRefusedException $e) {
            throw new OfficeRefusal($e->getMessage());
        }

        $this->journal->consigner($admin, AdminActionKind::BookingCancelled, $booking,
            "Réservation {$booking->reference} annulée ({$booking->traveller}).", $motif);
    }

    public function ecrire(Admin $admin, Booking $booking, string $corps): void
    {
        $this->fil->ecrire($booking, MessageAuthor::Vayla, $corps);

        $this->journal->consigner($admin, AdminActionKind::MessageWritten, $booking,
            "Message de Vayla dans le fil de {$booking->reference}.", $corps);
    }
}
