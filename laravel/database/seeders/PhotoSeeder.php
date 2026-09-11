<?php

namespace Database\Seeders;

use App\Models\Photo;
use Illuminate\Support\Arr;
use Illuminate\Database\Seeder;

/**
 * Photographies de Madagascar issues de Wikimedia Commons sous licence libre.
 *
 * Règle non négociable : uniquement de vraies photographies de Madagascar.
 * Aucune banque d'images générique — une plage des Maldives étiquetée
 * « Nosy Be » ferait exactement ce que Vayla reproche aux annonces volées.
 *
 * L'ordre d'insertion est l'ordre d'affichage du bloc « Crédits photo » du
 * pied de page. CC BY et CC BY-SA exigent ce bloc : ne pas le supprimer.
 *
 * Les fichiers vivent dans public/images/lieux/<key>-<largeur>.webp, en 800,
 * 1600 et 3200 px — mais jamais au-delà de la résolution de l'original :
 * `width` porte la plus grande réellement produite, et le `srcset` s'y arrête.
 * Les clés préfixées `an-` illustrent les annonces de démonstration ; elles
 * disparaissent le jour où de vraies annonces arrivent.
 */
class PhotoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->photos() as $photo) {
            $existante = Photo::query()->where('key', $photo['key'])->first();

            if (! $existante) {
                Photo::create($photo);

                continue;
            }

            // **La légende, l'auteur et la page d'origine se corrigent dans la
            // photothèque du back-office** : les réécrire à chaque `make seed`
            // effacerait en silence le travail de l'équipe — la règle des
            // autres référentiels. La licence et les fichiers restent ceux du
            // catalogue, que la photothèque ne touche pas pour Commons.
            $existante->fill(Arr::except($photo, ['caption', 'author', 'source_url']))->save();
        }

        // Une photo retirée du catalogue doit disparaître de la base, sinon
        // elle continue d'apparaître dans les « Crédits photo » du pied de
        // page alors qu'elle n'est plus affichée nulle part : on créditerait
        // un auteur pour une image qu'on ne publie plus.
        //
        // **Seulement dans `lieux`, le dossier que ce seeder possède.** La
        // suppression visait toute la table : un `make seed` effaçait les
        // photos téléversées par les propriétaires (`annonces`) et par
        // l'équipe (`destinations`), en laissant leurs fichiers orphelins.
        Photo::query()
            ->where('folder', 'lieux')
            ->whereNotIn('key', array_column($this->photos(), 'key'))
            ->delete();
    }

    /** @return array<int, array<string, ?string>> */
    private function photos(): array
    {
        return [
            [
                'key' => 'ampefy-itasy',
                'caption' => 'Ampefy, au bord du lac Itasy',
                'author' => 'Bluerose25',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Amefy.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'couchant-majunga',
                'caption' => 'Coucher de soleil près de Mahajanga (photo des années 1970)',
                'author' => 'Madagascar74',
                'licence' => 'CC BY-SA 3.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/3.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Madagascar74.130.jpg',
                'width' => 1600,
            ],
            [
                'key' => 'pirogue-ifaty',
                'caption' => 'Pirogues sur la plage d\'Ifaty, près de Tuléar',
                'author' => 'Smiley.toerist',
                'licence' => 'CC BY-SA 3.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/3.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Canoe_by_Ifaty_beach.jpg',
                'width' => 1600,
            ],
            [
                'key' => 'passerelle-andasibe',
                'caption' => 'Passerelle dans la forêt d\'Andasibe',
                'author' => 'Heinonlein',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Andasibe_01.JPG',
                'width' => 3200,
            ],
            [
                'key' => 'an-demeure-bois',
                'caption' => 'Demeure en bois à véranda, Madagascar',
                'author' => 'Dalbergiaada',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Demeure_en_bois.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-ixora-foulpointe',
                'caption' => 'Terrasse de l\'hôtel Ixora, Foulpointe',
                'author' => 'Rasfrench',
                'licence' => 'CC BY-SA 3.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/3.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Hotel_Ixora_Foulpointe.JPG',
                'width' => 1600,
            ],
            [
                'key' => 'an-thermes-front',
                'caption' => 'Façade de l\'hôtel des Thermes, Antsirabe',
                'author' => 'Hardscarf',
                'licence' => 'CC BY-SA 3.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/3.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Antsirabe_-_H%C3%B4tel_des_Thermes_-_front.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-plage-ifaty',
                'caption' => 'Plage d\'Ifaty, près de Tuléar',
                'author' => 'Bernard Gagnon',
                'licence' => 'CC BY-SA 3.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/3.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Ifaty_beach_Madagascar.jpg',
                'width' => 1600,
            ],
            [
                'key' => 'an-salon-emyrne',
                'caption' => 'Salon du Pavillon de l\'Emyrne, Antananarivo',
                'author' => 'VisitingMadagascar',
                'licence' => 'CC BY-SA 2.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/2.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Hotel_Le_Pavillon_de_l%27Emyrne.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'avenue-antsirabe',
                'caption' => 'Grande avenue d\'Antsirabe',
                'author' => 'Smiley.toerist',
                'licence' => 'CC BY-SA 3.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/3.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Grande_Avenue_d%27Antsirabe_IV.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'morondava-baobabs',
                'caption' => 'Allée des baobabs, près de Morondava',
                'author' => 'Bernard Gagnon',
                'licence' => 'CC BY-SA 3.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/3.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Adansonia_grandidieri01.jpg',
                'width' => 1600,
            ],
            [
                'key' => 'sainte-marie-crique',
                'caption' => 'La crique, île Sainte-Marie',
                'author' => 'M worm',
                'licence' => 'Public domain',
                'licence_url' => null,
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Beache_of_La_Crique_Sainte_Marie.jpg',
                'width' => 1600,
            ],
            [
                'key' => 'tana-rue',
                'caption' => 'Rue d\'Antananarivo',
                'author' => 'Olivier Lejade',
                'licence' => 'CC BY-SA 2.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/2.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Antananarivo_15.jpg',
                'width' => 1600,
            ],
            [
                'key' => 'foulpointe-bungalows',
                'caption' => 'Bungalows à Foulpointe',
                'author' => 'Hafidhou',
                'licence' => 'CC BY-SA 3.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/3.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Bungalows_la_salamandre_%C3%A0_foulpointe.JPG',
                'width' => 3200,
            ],
            [
                'key' => 'an-ambatoloaka-village',
                'caption' => 'Boutique du village d\'Ambatoloaka, Nosy Be',
                'author' => 'Moi daniele',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File%3A1_Ambatoloaka_village_Nosy_B%C3%A9_2013_%21.JPG',
                'width' => 3200,
            ],
            [
                'key' => 'an-ambatoloaka-rue',
                'caption' => 'Bord de plage et étals à Ambatoloaka, Nosy Be',
                'author' => 'Moi daniele',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File%3A7_Ambatoloaka_village_Nosy_B%C3%A9_2013_%21.JPG',
                'width' => 3200,
            ],
            [
                'key' => 'an-nosy-komba-hotel',
                'caption' => 'Enseigne d\'hôtel à Nosy Komba',
                'author' => 'VisitingMadagascar',
                'licence' => 'CC BY-SA 2.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/2.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File%3ANosy_Komba_Hotel.jpg',
                'width' => 1600,
            ],
            [
                'key' => 'an-ampasindava-hotel',
                'caption' => 'Le rivage d\'Ampasindava, Nosy Be',
                'author' => 'Soa.joe.P',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File%3AVillage_d%27Ampasindava_%28h%C3%B4tel%29.jpg',
                'width' => 1600,
            ],
            [
                'key' => 'an-itasy-lac',
                'caption' => 'Lac de cratère, région Itasy',
                'author' => 'Mendel264',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File%3ALac_volcanique_%28r%C3%A9gion_Itasy%29.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-mahajanga-corniche',
                'caption' => 'La corniche de Mahajanga',
                'author' => 'Da flow',
                'licence' => 'CC BY-SA 3.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/3.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File%3AMahajanga_Corniche.jpg',
                'width' => 1600,
            ],
            [
                'key' => 'an-majunga-ville',
                'caption' => 'Taxis-brousse au marché de Majunga',
                'author' => 'Lalinah',
                'licence' => 'CC0',
                'licence_url' => 'http://creativecommons.org/publicdomain/zero/1.0/deed.en',
                'source_url' => 'https://commons.wikimedia.org/wiki/File%3AMajunga_ville_01.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-majunga-bois-sacre',
                'caption' => 'Le quartier du bois sacré, Majunga',
                'author' => 'Tapa02',
                'licence' => 'CC0',
                'licence_url' => 'http://creativecommons.org/publicdomain/zero/1.0/deed.en',
                'source_url' => 'https://commons.wikimedia.org/wiki/File%3ABois_sacr%C3%A9_Majunga%2C_Madagascar_04.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-thermes-jardin',
                'caption' => 'Terrasse de l\'hôtel des Thermes, Antsirabe',
                'author' => 'Smiley.toerist',
                'licence' => 'CC BY-SA 3.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/3.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File%3AH%C3%B4tel_des_Thermes_Antsirabe_II.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-antsirabe-rue',
                'caption' => 'Pousse-pousse dans une rue d\'Antsirabe',
                'author' => 'Bernard Gagnon',
                'licence' => 'CC BY-SA 3.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/3.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File%3AAntsirabe_-_rue_principale02.JPG',
                'width' => 1600,
            ],
            [
                'key' => 'an-ifaty-couchant',
                'caption' => 'Coucher de soleil sur le lagon d\'Ifaty',
                'author' => 'Rod Waddington',
                'licence' => 'CC BY-SA 2.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/2.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File%3ASunset%2C_Ifaty%2C_Madagascar_%2821796862822%29.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-andasibe-parc',
                'caption' => 'Rivière dans la forêt d\'Andasibe',
                'author' => 'Rod Waddington',
                'licence' => 'CC BY-SA 2.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/2.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File%3AAnsicht_Nationalpark_Andasibe-Mantadia_in_Madagaskar.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-maria-lodge-jardin',
                'caption' => 'Caméléon dans le jardin d\'un lodge, Andasibe',
                'author' => 'Miarantsoa',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File%3ACam%C3%A9l%C3%A9on%2C_jardin_de_l%27h%C3%B4tel_Maria_Lodge_Andasibe.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'tamatave-plage',
                'caption' => 'Plage et port de Tamatave',
                'author' => 'M M from Switzerland',
                'licence' => 'CC BY-SA 2.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/2.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Tamatave,_Madagascar_(4674417432).jpg',
                'width' => 3200,
            ],
            [
                'key' => 'lagon-nosy-iranja',
                'caption' => 'Le lagon de Nosy Iranja, archipel de Nosy Be',
                'author' => 'Diego Delso',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Nosy_Iranja%2C_Madagascar%2C_2025-09-17%2C_DD_29.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-palmier-iranja',
                'caption' => 'Cocotier couché sur la plage de Nosy Iranja',
                'author' => 'Diego Delso',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Nosy_Iranja%2C_Madagascar%2C_2025-09-17%2C_DD_21.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-banc-sable-iranja',
                'caption' => 'Le banc de sable de Nosy Iranja',
                'author' => 'Diego Delso',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Nosy_Iranja%2C_Madagascar%2C_2025-09-17%2C_DD_25.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-pirogues-iranja',
                'caption' => 'Pirogues à balancier sur la plage de Nosy Iranja',
                'author' => 'Diego Delso',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Nosy_Iranja%2C_Madagascar%2C_2025-09-17%2C_DD_19.jpg',
                'width' => 1600,
            ],
            [
                'key' => 'an-plage-ampasipohy',
                'caption' => 'La plage d\'Ampasipohy, Nosy Be',
                'author' => 'Diego Delso',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Ampasipohy%2C_Nosy_Be%2C_Madagascar%2C_2025-09-21%2C_DD_16.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-village-ampasipohy',
                'caption' => 'Cases et jardins à Ampasipohy, Nosy Be',
                'author' => 'Diego Delso',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Ampasipohy%2C_Nosy_Be%2C_Madagascar%2C_2025-09-21%2C_DD_18.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-case-nosy-komba',
                'caption' => 'Case et pirogue sur la plage de Nosy Komba',
                'author' => 'Diego Delso',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Nosy_Komba%2C_Madagascar%2C_2025-09-12%2C_DD_07.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-village-nosy-komba',
                'caption' => 'Le village de Nosy Komba au bord de l\'eau',
                'author' => 'Diego Delso',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Nosy_Komba%2C_Madagascar%2C_2025-09-12%2C_DD_50.jpg',
                'width' => 1600,
            ],
            [
                'key' => 'an-boutre-antanimora',
                'caption' => 'Boutre échoué à marée basse, Nosy Antanimora',
                'author' => 'Diego Delso',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Nosy_Antanimora%2C_Madagascar%2C_2025-09-15%2C_DD_12.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-riviere-vohimana',
                'caption' => 'Rivière dans la forêt humide de Vohimana',
                'author' => 'Anai171',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Rivi%C3%A8re_dans_la_for%C3%AAt_humide_de_Madagascar_%2875978%29.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-villa-ampefy',
                'caption' => 'La villa Razaka et sa piscine, Ampefy',
                'author' => 'Cactus0625',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Villa_Razaka%2C_Ampefy_Madagascar_%2895694%29.jpg',
                'width' => 1600,
            ],
            [
                'key' => 'an-lac-ampefy',
                'caption' => 'Le lac Itasy vu d\'Ampefy',
                'author' => 'Cactus0625',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Kung-fu_H%C3%B4tel_Ampefy%2C_Madagascar_%2899784%29.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-indri-andasibe',
                'caption' => 'Un indri dans la forêt d\'Andasibe',
                'author' => 'MPMF24',
                'licence' => 'CC BY-SA 4.0',
                'licence_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Indri_indri.jpg',
                'width' => 3200,
            ],
            [
                'key' => 'an-riviere-andasibe',
                'caption' => 'Rivière du parc national d\'Andasibe',
                'author' => 'Brussels',
                'licence' => 'CC BY 2.0',
                'licence_url' => 'https://creativecommons.org/licenses/by/2.0',
                'source_url' => 'https://commons.wikimedia.org/wiki/File:Andasibe_National_Park%2C_Madagascar_%284027581120%29.jpg',
                'width' => 3200,
            ],
        ];
    }
}
