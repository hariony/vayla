<?php

namespace App\Console\Commands;

use App\Services\Bookings\PendingReminders;
use Illuminate\Console\Command;

/**
 * Le rappel avant expiration.
 *
 * **C'est la notification qui rattrape le plus de demandes.** Celui qui n'a
 * pas répondu au premier message n'a en général pas décidé de refuser : il a
 * oublié. Sans ce rappel, la demande expire, les nuits repartent au
 * calendrier, le voyageur cherche ailleurs — et les deux parties perdent un
 * séjour que personne ne voulait annuler.
 *
 * **Une seule fois, à un seul moment.** Deux rappels feraient de Vayla un
 * expéditeur qu'on met en sourdine, et le jour où le message compte il ne
 * serait plus lu. Le seuil est à un tiers du délai restant : assez tôt pour
 * qu'on puisse encore appeler le voyageur, assez tard pour ne pas doubler le
 * message d'arrivée.
 */
class RemindPendingBookings extends Command
{
    protected $signature = 'vayla:remind-pending-bookings';

    protected $description = 'Rappelle aux propriétaires les demandes sur le point d’expirer';

    public function handle(PendingReminders $rappels): int
    {
        $n = $rappels->rappeler();

        $this->info($n === 0
            ? 'Aucune demande à rappeler.'
            : $n.' rappel(s) mis en file. `php artisan vayla:whatsapp` pour les envoyer.');

        return self::SUCCESS;
    }
}
