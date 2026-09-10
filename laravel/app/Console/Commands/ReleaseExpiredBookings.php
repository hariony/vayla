<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

/**
 * Rend au calendrier les nuits des demandes restées sans réponse.
 *
 * Sans cette commande, une demande déposée puis oubliée fermerait les dates
 * indéfiniment. C'est la contrepartie du blocage immédiat : on retire des
 * nuits tout de suite pour éviter la double réservation, on les rend vite
 * si personne ne répond.
 */
class ReleaseExpiredBookings extends Command
{
    protected $signature = 'vayla:release-expired-bookings';

    protected $description = 'Libère les nuits des demandes de réservation sans réponse';

    public function handle(BookingService $bookings): int
    {
        $n = $bookings->releaseExpired();

        $this->info($n === 0
            ? 'Aucune demande à libérer.'
            : "{$n} demande(s) expirée(s), nuits rendues au calendrier.");

        return self::SUCCESS;
    }
}
