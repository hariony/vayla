<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * L'ordre compte : les annonces référencent destinations, photos et
     * équipements. AmenitySeeder pose le vocabulaire, ListingSeeder s'y
     * rattache — et lève si une clé n'existe pas, plutôt que de créer une
     * annonce silencieusement amputée.
     *
     * ListingSeeder ne pose que des annonces de démonstration. Le jour où de
     * vraies annonces arrivent, il ne doit plus tourner en production.
     */
    public function run(): void
    {
        $this->call([
            PhotoSeeder::class,
            CategorySeeder::class,
            DestinationSeeder::class,
            AmenitySeeder::class,
            ListingSeeder::class,
            OwnerSeeder::class,
            UnavailabilitySeeder::class,
            BookingSeeder::class,
            StayConfirmationSeeder::class,
        ]);
    }
}
