<?php

namespace App\Services\Owners;

use App\Contracts\Repositories\OwnerSpaceRepositoryInterface;
use App\Data\Owners\OwnerBookingCountData;
use App\Data\Owners\OwnerBookingData;
use App\Data\Owners\OwnerBookingPageData;
use App\Data\Owners\OwnerBookingsPageData;
use App\Enums\BookingStatus;
use App\Enums\MessageAuthor;
use App\Models\Booking;
use App\Models\Owner;
use App\Services\ConversationService;
use Illuminate\Support\Collection;

/**
 * L'historique des réservations du propriétaire : **qui est venu, qu'est-ce qui
 * a été refusé, qu'est-ce qui a expiré**. Sans lui, une demande répondue
 * disparaissait de l'espace.
 */
final class OwnerBookingsQuery
{
    public function __construct(
        private OwnerSpaceRepositoryInterface $espace,
        private OwnerSpace $portee,
        private ConversationService $conversations,
    ) {}

    /** `$statut` nul : toutes. */
    public function liste(Owner $owner, ?BookingStatus $statut): OwnerBookingsPageData
    {
        $toutes = $this->espace->reservations($owner);

        return new OwnerBookingsPageData(
            bookings: $toutes
                ->sortByDesc('arrival')
                ->when($statut, fn (Collection $c) => $c->filter(fn (Booking $b) => $b->status === $statut))
                ->map(fn (Booking $b) => OwnerBookingData::fromModel($b))
                ->values()
                ->all(),
            filtre: $statut?->value ?? 'tous',
            compteurs: $this->compteurs($toutes),
        );
    }

    /** Ouvrir la réservation vaut lecture de son fil. */
    public function fiche(Owner $owner, string $reference): OwnerBookingPageData
    {
        $booking = $this->portee->reservation($owner, $reference);

        $this->conversations->marquerLu($booking, MessageAuthor::Owner);

        return new OwnerBookingPageData(
            booking: OwnerBookingData::fromModel($booking),
            messages: $this->conversations->fil($booking, MessageAuthor::Owner),
        );
    }

    /**
     * « Toutes », puis un état par pastille — **seulement ceux qui ramènent
     * quelque chose** : un filtre vide n'a pas à occuper une pastille.
     *
     * @return list<OwnerBookingCountData>
     */
    private function compteurs(Collection $toutes): array
    {
        $etats = collect(BookingStatus::cases())
            ->map(fn (BookingStatus $s) => new OwnerBookingCountData($s->value, $s->label(), $toutes->where('status', $s)->count()))
            ->filter(fn (OwnerBookingCountData $e) => $e->n > 0)
            ->values()
            ->all();

        return [new OwnerBookingCountData('tous', 'Toutes', $toutes->count()), ...$etats];
    }
}
