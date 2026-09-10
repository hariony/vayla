<?php

namespace App\Repositories\Contracts;

use App\Enums\MessageAuthor;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\Owner;
use Illuminate\Support\Collection;

/**
 * Le fil d'échange d'une réservation.
 *
 * Toutes les méthodes prennent la réservation : c'est la portée de sécurité
 * du fil. Une signature qui accepterait un identifiant de message nu
 * permettrait de lire ou d'écrire dans la conversation d'un inconnu.
 */
interface BookingMessageRepositoryInterface
{
    /** @return Collection<int, BookingMessage> */
    public function fil(Booking $booking): Collection;

    public function ecrire(Booking $booking, MessageAuthor $auteur, string $corps): BookingMessage;

    /** Marque le fil comme lu par cette partie, maintenant. */
    public function marquerLu(Booking $booking, MessageAuthor $partie): void;

    /** Combien de réservations du propriétaire portent un message qu'il n'a pas lu. */
    public function nonLusPour(Owner $owner): int;
}
