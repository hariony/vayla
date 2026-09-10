<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Listing;
use App\Models\Photo;
use App\Models\StayConfirmation;
use App\Models\Unavailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * `make seed` doit pouvoir se rejouer sans rien dupliquer — **y compris le
 * lendemain**.
 *
 * Deux seeders calculaient leurs dates depuis `Carbon::today()` et s'en
 * servaient comme clé de `updateOrCreate` : le jour suivant, la clé avait
 * glissé, la ligne de la veille n'était plus reconnue et une seconde était
 * créée. Résultat observé en développement : chaque avis affiché deux fois
 * côte à côte, et chaque période du calendrier bloquée en double — invisible
 * à l'œil, mais une nuit doublement bloquée ne se libère qu'à moitié.
 *
 * Le test rejoue les seeders **un jour plus tard** : c'est le seul scénario
 * qui attrapait le défaut.
 */
class SeederIdempotenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejouer_les_seeders_le_lendemain_ne_duplique_rien(): void
    {
        $this->seed();

        $avant = $this->recensement();

        Carbon::setTestNow(Carbon::now()->addDay());
        $this->seed();
        Carbon::setTestNow();

        $this->assertSame($avant, $this->recensement(),
            'Rejouer les seeders un jour plus tard a changé le nombre de lignes.');
    }

    /** @return array<string, int> */
    private function recensement(): array
    {
        return [
            'photos' => Photo::query()->count(),
            'listings' => Listing::query()->count(),
            'unavailabilities' => Unavailability::query()->count(),
            'confirmations' => StayConfirmation::query()->count(),
            'bookings' => Booking::query()->count(),
        ];
    }
}
