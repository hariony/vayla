<?php

namespace App\Repositories;

use App\Enums\MessageAuthor;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\Owner;
use App\Repositories\Contracts\BookingMessageRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BookingMessageRepository implements BookingMessageRepositoryInterface
{
    public function fil(Booking $booking): Collection
    {
        return $booking->messages()->get();
    }

    public function ecrire(Booking $booking, MessageAuthor $auteur, string $corps): BookingMessage
    {
        $message = BookingMessage::create([
            'booking_id' => $booking->id,
            'author' => $auteur,
            'body' => $corps,
        ]);

        // Écrire, c'est avoir lu : sans ça, son propre message compterait
        // comme non lu pour lui-même à la prochaine ouverture.
        $this->marquerLu($booking, $auteur);

        return $message;
    }

    public function marquerLu(Booking $booking, MessageAuthor $partie): void
    {
        $colonne = match ($partie) {
            MessageAuthor::Owner, MessageAuthor::Vayla => 'owner_read_at',
            MessageAuthor::Traveller => 'traveller_read_at',
        };

        $booking->forceFill([$colonne => Carbon::now()])->save();
    }

    /**
     * Le compte affiché sur l'onglet « Réservations ».
     *
     * On compte les **réservations**, pas les messages : « 2 » à côté d'un
     * onglet veut dire « deux conversations vous attendent », pas « quatorze
     * lignes de texte ». Un compte de messages ferait paniquer pour un
     * voyageur bavard.
     */
    public function nonLusPour(Owner $owner): int
    {
        return Booking::query()
            ->whereIn('listing_id', $owner->listings->pluck('id'))
            ->whereHas('messages', function ($q) {
                $q->where('author', '!=', MessageAuthor::Owner->value)
                    ->where(function ($neuf) {
                        // Un fil jamais ouvert : tout message de l'autre
                        // partie y est neuf. La condition tient dans **une
                        // seule clause** : un `orWhere` au premier niveau
                        // aurait, à la première retouche, ramené les
                        // réservations d'autres propriétaires.
                        $neuf->whereNull('bookings.owner_read_at')
                            ->orWhereColumn('booking_messages.created_at', '>', 'bookings.owner_read_at');
                    });
            })
            ->count();
    }
}
