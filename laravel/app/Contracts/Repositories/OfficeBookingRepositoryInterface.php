<?php

namespace App\Contracts\Repositories;

use App\Enums\BookingQueueFilter;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Owner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Les réservations, vues par l'équipe. */
interface OfficeBookingRepositoryInterface
{
    /** @return array<string, int> par onglet de `BookingQueueFilter`, « Toutes » exclu */
    public function comptesParFiltre(): array;

    public function nombreAuStatut(BookingStatus $statut): int;

    /** @return LengthAwarePaginator<int, Booking> une demande qui attend se trie par son échéance */
    public function paginer(BookingQueueFilter $filtre, ?string $recherche, int $parPage): LengthAwarePaginator;

    /** @return Collection<int, Booking> les demandes qui expirent dans les `heures` qui viennent */
    public function urgentes(int $heures, int $limite): Collection;

    public function nombreExpirantSous(int $heures): int;

    public function nombreCreeesDepuis(Carbon $depuis): int;

    public function nombreSejoursEffectuesDepuis(Carbon $depuis): int;

    /** @return Collection<int, Booking> */
    public function dernieresDuProprietaire(Owner $owner, int $limite): Collection;

    /** Charge ce que la fiche lit : l'annonce, son propriétaire, sa destination. */
    public function pourFiche(Booking $booking): Booking;
}
