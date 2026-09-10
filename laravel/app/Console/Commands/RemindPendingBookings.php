<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\Notifications\OwnerNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

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

    public function handle(OwnerNotifier $notifier): int
    {
        $seuil = Carbon::now()->addHours(max(2, (int) round(config('vayla.booking.hold_hours') / 3)));

        $demandes = Booking::query()
            ->with('listing.owner')
            ->where('status', BookingStatus::Pending->value)
            ->whereNotNull('hold_expires_at')
            // Ni déjà expirée — la libération s'en charge — ni encore loin.
            ->where('hold_expires_at', '>', Carbon::now())
            ->where('hold_expires_at', '<=', $seuil)
            ->get();

        foreach ($demandes as $demande) {
            $notifier->demandeExpireBientot($demande);
        }

        $this->info($demandes->isEmpty()
            ? 'Aucune demande à rappeler.'
            : $demandes->count().' rappel(s) mis en file. `php artisan vayla:whatsapp` pour les envoyer.');

        return self::SUCCESS;
    }
}
