<?php

namespace App\Contracts\Bookings;

use App\Enums\MessageAuthor;
use App\Models\Booking;
use App\Models\BookingMessage;

/**
 * Le fil d'une réservation : **une trace rattachée à un séjour**, lue par les
 * deux parties. Implémenté par `ConversationService`.
 */
interface BookingThread
{
    /** @return array<int, array<string, mixed>> */
    public function fil(Booking $booking, MessageAuthor $lecteur): array;

    public function ecrire(Booking $booking, MessageAuthor $auteur, string $corps): BookingMessage;
}
