<?php

namespace App\Repositories;

use App\Contracts\Repositories\BookingRepositoryInterface;
use App\DTOs\Bookings\BookingTermsDto;
use App\DTOs\Bookings\NewBookingDto;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Owner;
use App\Models\StayConfirmation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BookingRepository implements BookingRepositoryInterface
{
    public function verrouiller(Listing $listing): void
    {
        Booking::query()
            ->where('listing_id', $listing->id)
            ->whereIn('status', array_column(BookingStatus::blocking(), 'value'))
            ->lockForUpdate()
            ->get();
    }

    public function creer(Listing $listing, NewBookingDto $demande, BookingTermsDto $termes): Booking
    {
        return Booking::create([
            'reference' => $termes->reference,
            'listing_id' => $listing->id,
            'traveller' => $demande->traveller,
            'traveller_phone' => $demande->travellerPhone,
            'traveller_email' => $demande->travellerEmail,
            'guests' => $demande->guests,
            'message' => $demande->message,
            'arrival' => $demande->arrival,
            'departure' => $demande->departure,
            'nights' => $termes->nights,
            'price_per_night' => $termes->pricePerNight,
            'total' => $termes->total,
            'commission_rate' => $termes->commissionRate,
            'status' => BookingStatus::Pending,
            'hold_expires_at' => $termes->holdExpiresAt,
        ]);
    }

    public function referenceExiste(string $reference): bool
    {
        return Booking::query()->where('reference', $reference)->exists();
    }

    public function parReference(string $reference): ?Booking
    {
        return Booking::query()->where('reference', $reference)->first();
    }

    public function pourConfirmation(string $reference): ?Booking
    {
        return Booking::query()
            ->with(['listing.destination', 'listing.photos', 'listing.categories', 'listing.amenities', 'listing.owner'])
            ->where('reference', $reference)
            ->first();
    }

    public function accepter(Booking $booking): void
    {
        $booking->update(['status' => BookingStatus::Accepted, 'answered_at' => Carbon::now(), 'hold_expires_at' => null]);
    }

    public function refuser(Booking $booking, ?string $motif): void
    {
        $booking->update([
            'status' => BookingStatus::Declined,
            'answered_at' => Carbon::now(),
            'hold_expires_at' => null,
            'closed_reason' => $motif,
        ]);
    }

    public function annuler(Booking $booking, ?string $motif): void
    {
        $booking->update(['status' => BookingStatus::Cancelled, 'hold_expires_at' => null, 'closed_reason' => $motif]);
    }

    public function terminer(Booking $booking, StayConfirmation $confirmation): void
    {
        $confirmation->update(['booking_id' => $booking->id]);

        $booking->update([
            'status' => BookingStatus::Completed,
            'completed_at' => $confirmation->confirmed_at ?? Carbon::now(),
        ]);
    }

    public function effectuesDuProprietaire(Owner $owner, Carbon $debut, Carbon $finExclue): Collection
    {
        return Booking::query()
            ->with('listing')
            ->whereHas('listing', fn ($q) => $q->where('owner_id', $owner->id))
            ->where('status', BookingStatus::Completed->value)
            // **Intervalle semi-ouvert, jamais `whereBetween` sur une date.**
            // `bookings.departure` est une colonne `date`, mais SQLite est
            // faiblement typé et y range ce que Laravel écrit — `2026-08-31
            // 00:00:00`. La comparaison redevient alors une comparaison de
            // chaînes, où cette valeur est *supérieure* à la borne haute
            // `2026-08-31` parce qu'elle est plus longue : le séjour qui se
            // termine le dernier jour du mois sortait de sa facture, et
            // n'entrait pas non plus dans la suivante. `< premier jour du mois
            // suivant` est juste sur les deux moteurs et reste indexable.
            ->where('departure', '>=', $debut->toDateString())
            ->where('departure', '<', $finExclue->toDateString())
            ->orderBy('departure')
            ->get();
    }

    public function expirantAvant(Carbon $seuil): Collection
    {
        return Booking::query()
            ->with('listing.owner')
            ->where('status', BookingStatus::Pending->value)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '>', Carbon::now())
            ->where('hold_expires_at', '<=', $seuil)
            ->get();
    }

    public function expirerLesDemandes(): int
    {
        return Booking::query()
            ->where('status', BookingStatus::Pending->value)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<', Carbon::now())
            ->update([
                'status' => BookingStatus::Expired->value,
                'closed_reason' => 'Sans réponse du propriétaire',
            ]);
    }
}
