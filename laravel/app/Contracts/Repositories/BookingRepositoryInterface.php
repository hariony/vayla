<?php

namespace App\Contracts\Repositories;

use App\DTOs\Bookings\BookingTermsDto;
use App\DTOs\Bookings\NewBookingDto;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Owner;
use App\Models\StayConfirmation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Les réservations, côté voyageur et côté propriétaire : leur naissance et leurs réponses. */
interface BookingRepositoryInterface
{
    /**
     * Verrouille les réservations bloquantes d'une annonce jusqu'à la fin de la
     * transaction : sans ce verrou, deux demandes simultanées passeraient
     * toutes deux le contrôle de chevauchement.
     */
    public function verrouiller(Listing $listing): void;

    public function creer(Listing $listing, NewBookingDto $demande, BookingTermsDto $termes): Booking;

    public function referenceExiste(string $reference): bool;

    public function parReference(string $reference): ?Booking;

    /** Avec l'annonce, sa destination, ses photos, ses catégories, ses équipements et son propriétaire. */
    public function pourConfirmation(string $reference): ?Booking;

    public function accepter(Booking $booking): void;

    public function refuser(Booking $booking, ?string $motif): void;

    public function annuler(Booking $booking, ?string $motif): void;

    /** Rattache la confirmation du voyageur, et rend le séjour facturable. */
    public function terminer(Booking $booking, StayConfirmation $confirmation): void;

    /**
     * Les séjours **effectués** d'un propriétaire partis dans `[$debut, $finExclue[`,
     * avec leur annonce, dans l'ordre des départs : les lignes d'une facture.
     *
     * @return Collection<int, Booking>
     */
    public function effectuesDuProprietaire(Owner $owner, Carbon $debut, Carbon $finExclue): Collection;

    /**
     * Les demandes encore ouvertes dont le délai se termine d'ici `$seuil` —
     * ni déjà expirées, ni encore loin —, avec le propriétaire de l'annonce.
     *
     * @return Collection<int, Booking>
     */
    public function expirantAvant(Carbon $seuil): Collection;

    /** Les demandes dont le délai a coulé passent en « expirée ». Rend leur nombre. */
    public function expirerLesDemandes(): int;
}
