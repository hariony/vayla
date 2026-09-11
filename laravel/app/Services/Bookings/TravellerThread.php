<?php

namespace App\Services\Bookings;

use App\Contracts\Repositories\BookingRepositoryInterface;
use App\Enums\MessageAuthor;
use App\Exceptions\BookingNotFoundException;
use App\Services\ConversationService;

/**
 * Le voyageur écrit dans le fil de sa réservation.
 *
 * **Il n'a pas de compte, et c'est délibéré** : la référence tient lieu de
 * droit d'accès, comme pour la page elle-même. La limite de débit de la route
 * tient la porte — une référence courte se devine à force d'essais.
 */
final class TravellerThread
{
    public function __construct(
        private BookingRepositoryInterface $reservations,
        private ConversationService $conversations,
    ) {}

    /** @throws BookingNotFoundException */
    public function ecrire(string $reference, string $corps): void
    {
        $booking = $this->reservations->parReference($reference) ?? throw new BookingNotFoundException($reference);

        $this->conversations->ecrire($booking, MessageAuthor::Traveller, $corps);
    }
}
