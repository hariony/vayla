<?php

namespace App\Services\Bookings;

use App\Contracts\Repositories\BookingRepositoryInterface;
use App\Services\Notifications\OwnerNotifier;
use Illuminate\Support\Carbon;

/**
 * Le rappel avant expiration : **la notification qui rattrape le plus de
 * demandes** — celui qui n'a pas répondu n'a en général pas décidé de refuser,
 * il a oublié. Il part une fois, à un tiers du délai restant : assez tôt pour
 * pouvoir encore appeler le voyageur, assez tard pour ne pas doubler le
 * message d'arrivée. `OwnerNotifier` refuse de l'écrire deux fois.
 */
final class PendingReminders
{
    public function __construct(
        private BookingRepositoryInterface $reservations,
        private OwnerNotifier $notifier,
    ) {}

    /** @return int le nombre de rappels mis en file */
    public function rappeler(): int
    {
        $seuil = Carbon::now()->addHours(max(2, (int) round(config('vayla.booking.hold_hours') / 3)));
        $demandes = $this->reservations->expirantAvant($seuil);

        foreach ($demandes as $demande) {
            $this->notifier->demandeExpireBientot($demande);
        }

        return $demandes->count();
    }
}
