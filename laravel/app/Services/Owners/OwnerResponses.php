<?php

namespace App\Services\Owners;

use App\Enums\MessageAuthor;
use App\Exceptions\BookingNotFoundException;
use App\Exceptions\BookingRefusedException;
use App\Models\Owner;
use App\Services\BookingService;
use App\Services\ConversationService;

/**
 * Ce que le propriétaire répond : accepter, refuser, écrire — **toujours sur
 * une réservation qui porte sur l'un de ses logements** (`OwnerSpace`).
 */
final class OwnerResponses
{
    public function __construct(
        private OwnerSpace $portee,
        private BookingService $reservations,
        private ConversationService $conversations,
    ) {}

    /** @throws BookingNotFoundException|BookingRefusedException */
    public function accepter(Owner $owner, string $reference): void
    {
        $this->reservations->accept($this->portee->reservation($owner, $reference));
    }

    /**
     * Le motif part au voyageur : un refus sans explication use la relation
     * des deux côtés.
     *
     * @throws BookingNotFoundException|BookingRefusedException
     */
    public function refuser(Owner $owner, string $reference, ?string $motif): void
    {
        $this->reservations->decline($this->portee->reservation($owner, $reference), $motif);
    }

    /** @throws BookingNotFoundException */
    public function ecrire(Owner $owner, string $reference, string $corps): void
    {
        $this->conversations->ecrire($this->portee->reservation($owner, $reference), MessageAuthor::Owner, $corps);
    }
}
