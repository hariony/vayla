<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\Photo;
use Illuminate\Database\Seeder;

/**
 * Les destinations ouvertes. `opened_at` reste nul tant que la date
 * d'ouverture réelle n'est pas connue — on ne remplit pas une colonne de
 * date avec une invention.
 *
 * L'ordre d'affichage n'est pas ici : le DestinationService trie la vedette
 * d'abord, puis les destinations qui ont des logements.
 */
class DestinationSeeder extends Seeder
{
    public function run(): void
    {
        $photos = Photo::query()->pluck('id', 'key');

        $destinations = [
            ['slug' => 'nosy-be', 'airport_code' => 'NOS', 'airport_name' => 'Fascene', 'flight_from_tana' => '1 h 15', 'road_route' => 'RN4 puis RN6 jusqu\'à Ankify, puis bac', 'road_km' => 1150, 'road_hours' => '24 h et plus', 'climate_zone' => 'nord',      'name' => 'Nosy Be',      'region' => 'Diana',            'tagline' => 'Lagons turquoise et villas les pieds dans l\'eau', 'scene' => 'lagoon',   'photo' => 'lagon-nosy-iranja',    'featured' => true],
            ['slug' => 'ampefy', 'road_route' => 'RN1', 'road_km' => 120, 'road_hours' => '2 h 30 à 3 h', 'climate_zone' => 'hautes-terres',       'name' => 'Ampefy',       'region' => 'Itasy',            'tagline' => 'L\'escapade week-end des Tananariviens',           'scene' => 'lake',     'photo' => 'ampefy-itasy',        'featured' => false],
            ['slug' => 'majunga', 'airport_code' => 'MJN', 'airport_name' => 'Amborovy', 'flight_from_tana' => '1 h 10', 'road_route' => 'RN4', 'road_km' => 570, 'road_hours' => '9 à 12 h', 'climate_zone' => 'ouest',      'name' => 'Majunga',      'region' => 'Boeny',            'tagline' => 'Le bord de mer et ses couchers de soleil',         'scene' => 'sunset',   'photo' => 'couchant-majunga',    'featured' => false],
            ['slug' => 'antsirabe', 'road_route' => 'RN7', 'road_km' => 170, 'road_hours' => '3 à 4 h', 'climate_zone' => 'hautes-terres',    'name' => 'Antsirabe',    'region' => 'Vakinankaratra',   'tagline' => 'Air frais des hautes terres',                      'scene' => 'highland', 'photo' => 'avenue-antsirabe',    'featured' => false],
            ['slug' => 'tulear', 'airport_code' => 'TLE', 'airport_name' => 'Toliara', 'flight_from_tana' => '1 h 30', 'road_route' => 'RN7', 'road_km' => 950, 'road_hours' => '2 jours', 'road_note' => 'Ifaty est à 27 km au nord de Tuléar, par une piste sableuse.', 'climate_zone' => 'sud',       'name' => 'Tuléar',       'region' => 'Atsimo-Andrefana', 'tagline' => 'Sable blanc et récif corallien',                   'scene' => 'beach',    'photo' => 'pirogue-ifaty',       'featured' => false],
            ['slug' => 'andasibe', 'road_route' => 'RN2', 'road_km' => 140, 'road_hours' => '3 à 4 h', 'climate_zone' => 'est-foret',     'name' => 'Andasibe',     'region' => 'Alaotra-Mangoro',  'tagline' => 'Forêt primaire et lémuriens',                      'scene' => 'forest',   'photo' => 'passerelle-andasibe', 'featured' => false],
            ['slug' => 'sainte-marie', 'airport_code' => 'SMS', 'airport_name' => 'Sainte-Marie', 'flight_from_tana' => '1 h', 'road_route' => 'RN2 jusqu\'à Soanierana-Ivongo, puis bateau', 'road_km' => 430, 'road_hours' => '10 à 12 h, puis 1 h 30 de mer', 'climate_zone' => 'est', 'name' => 'Sainte-Marie', 'region' => 'Analanjirofo',     'tagline' => 'L\'île aux baleines, au large de la côte est',     'scene' => 'beach',    'photo' => 'sainte-marie-crique', 'featured' => false],
            ['slug' => 'tamatave', 'airport_code' => 'TMM', 'airport_name' => 'Toamasina', 'flight_from_tana' => '50 min', 'road_route' => 'RN2', 'road_km' => 350, 'road_hours' => '6 à 8 h', 'climate_zone' => 'est',     'name' => 'Tamatave',     'region' => 'Atsinanana',       'tagline' => 'Le grand port de l\'Est et ses plages',            'scene' => 'lagoon',   'photo' => 'tamatave-plage',      'featured' => false],
            ['slug' => 'foulpointe', 'road_route' => 'RN2 puis RN5', 'road_km' => 410, 'road_hours' => '7 à 9 h', 'climate_zone' => 'est',   'name' => 'Foulpointe',   'region' => 'Analanjirofo',     'tagline' => 'Un lagon fermé par la barrière de corail',         'scene' => 'lagoon',   'photo' => 'foulpointe-bungalows', 'featured' => false],
            ['slug' => 'antananarivo', 'airport_code' => 'TNR', 'airport_name' => 'Ivato', 'road_note' => 'C\'est d\'ici que partent toutes les routes nationales.', 'climate_zone' => 'hautes-terres', 'name' => 'Antananarivo', 'region' => 'Analamanga',       'tagline' => 'La capitale, ses collines et ses escaliers',       'scene' => 'highland', 'photo' => 'tana-rue',            'featured' => false],
            ['slug' => 'morondava', 'airport_code' => 'MOB', 'airport_name' => 'Morondava', 'flight_from_tana' => '1 h 15', 'road_route' => 'RN34 puis RN35', 'road_km' => 700, 'road_hours' => '14 à 18 h', 'climate_zone' => 'ouest',    'name' => 'Morondava',    'region' => 'Menabe',           'tagline' => 'L\'allée des baobabs au coucher du soleil',        'scene' => 'sunset',   'photo' => 'morondava-baobabs',   'featured' => false],
        ];

        foreach ($destinations as $destination) {
            $photoKey = $destination['photo'];
            unset($destination['photo']);

            // Créer ce qui manque, ne jamais réécrire : le back-office édite
            // les destinations, et la base fait foi. Seule une photo perdue
            // est reposée — une destination sans photo retombe sur un dessin.
            $existante = Destination::query()->where('slug', $destination['slug'])->first();

            if ($existante) {
                if (! $existante->photo_id && isset($photos[$photoKey])) {
                    $existante->update(['photo_id' => $photos[$photoKey]]);
                }
            }

            $cible = $existante ?? Destination::create(
                $destination + ['photo_id' => $photos[$photoKey] ?? null]
            );

            // La couverture est la première photo de la galerie : une
            // destination semée sans galerie la reçoit, jamais au-delà — le
            // reste de la galerie appartient à l'équipe.
            if ($cible->photo_id && ! $cible->galerie()->exists()) {
                $cible->galerie()->attach($cible->photo_id, ['position' => 0]);
            }
        }
    }
}
