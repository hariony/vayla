<?php

namespace App\Services\Bookings;

use App\Contracts\Repositories\BookingRepositoryInterface;
use App\Data\BookingData;
use App\Data\Pages\BookingConfirmedPageData;
use App\Enums\MessageAuthor;
use App\Exceptions\BookingNotFoundException;
use App\Services\ConversationService;
use App\Services\PhotoService;

/**
 * La page d'une réservation, atteinte par sa référence — le lien que porte le
 * message de confirmation. **L'ouvrir vaut lecture du fil** : un bouton
 * « marquer comme lu » de plus n'apprendrait rien à personne.
 */
final class BookingConfirmationQuery
{
    public function __construct(
        private BookingRepositoryInterface $reservations,
        private ConversationService $conversations,
        private PhotoService $photos,
    ) {}

    /** @throws BookingNotFoundException */
    public function page(string $reference): BookingConfirmedPageData
    {
        $booking = $this->reservations->pourConfirmation($reference) ?? throw new BookingNotFoundException($reference);

        $this->conversations->marquerLu($booking, MessageAuthor::Traveller);

        return new BookingConfirmedPageData(
            booking: BookingData::fromModel($booking),
            holdHours: (int) config('vayla.booking.hold_hours'),
            messages: $this->conversations->fil($booking, MessageAuthor::Traveller),
            photos: $this->photos->map(),
            credits: $this->photos->credits(),
        );
    }
}
