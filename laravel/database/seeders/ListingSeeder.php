<?php

namespace Database\Seeders;

use App\Enums\ListingStatus;
use App\Enums\PropertyType;
use App\Enums\TrustLevel;
use App\Models\Amenity;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Listing;
use App\Models\Photo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Les huit annonces de démonstration.
 *
 * Elles sont FICTIVES : elles n'existent que pour dessiner et éprouver la
 * grille tant qu'aucun propriétaire n'a publié. `is_demo` les marque, le
 * bandeau « Aperçu » le dit à l'écran, et la légende du crédit nomme le
 * vrai lieu photographié.
 *
 * `trust` renvoie au niveau de l'échelle de confiance : c'est la donnée qui
 * distingue Vayla d'un mur de photos.
 *
 * La catégorie « verifie » n'est jamais dans `tags` : elle se déduit du
 * niveau 4 (voir ListingData et ListingRepository).
 *
 * Les galeries ont été montées **après avoir regardé chaque photo**, pas
 * d'après le titre du fichier sur Commons : trois candidates portaient un
 * titre convaincant et montraient une voiture dans une rue, un portrait de
 * personne et un cadrage illisible. Elles ont été écartées, fichiers compris.
 *
 * Les galeries sont **cohérentes avec la région** : une photo de Foulpointe
 * sous une annonce de Majunga ferait exactement ce que Vayla reproche aux
 * annonces volées, bandeau « Aperçu » ou pas. Chaque photo garde sa propre
 * légende, qui nomme le vrai lieu photographié.
 *
 * Les jeux d'équipements sont volontairement inégaux, et c'est le point :
 * la case d'Ifaty n'a ni JIRAMA ni salle de bain privative, la villa de
 * Nosy Be a un groupe électrogène et un gardien. Une démonstration où tout
 * le monde a tout ne prouverait rien — ni que les rubriques tiennent, ni
 * que l'absence se lit aussi bien que la présence.
 */
class ListingSeeder extends Seeder
{
    public function run(): void
    {
        $destinations = Destination::query()->pluck('id', 'slug');
        $photos = Photo::query()->pluck('id', 'key');
        $categories = Category::query()->pluck('id', 'key');
        $amenities = Amenity::query()->pluck('id', 'key');

        foreach ($this->listings() as $row) {
            $listing = Listing::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'title' => $row['title'],
                    'destination_id' => $destinations[$row['destination']],
                    'scene' => $row['scene'],
                    'kind' => PropertyType::from($row['kind']),
                    'summary' => $row['summary'],
                    'description' => $row['description'],
                    'guests' => $row['guests'],
                    'bedrooms' => $row['bedrooms'],
                    'beds' => $row['beds'],
                    'bathrooms' => $row['bathrooms'],
                    'surface' => $row['surface'],
                    'price' => $row['price'],
                    'min_nights' => $row['min_nights'],
                    'max_nights' => $row['max_nights'] ?? null,
                    'check_in_from' => $row['check_in'] ?? '14:00',
                    'check_out_before' => $row['check_out'] ?? '11:00',
                    'pets_allowed' => $row['pets'] ?? false,
                    'smoking_allowed' => false,
                    'events_allowed' => $row['events'] ?? false,
                    'trust_level' => TrustLevel::from($row['trust']),
                    'featured' => $row['featured'],
                    'is_demo' => true,
                    'status' => ListingStatus::Published,
                ]
            );

            $listing->categories()->sync(
                collect($row['tags'])->map(fn (string $key) => $categories[$key])->all()
            );

            $listing->amenities()->sync($this->pivot($row, $amenities));

            // L'ordre du tableau EST l'ordre de la galerie, et la première
            // photo EST la couverture. Rien d'autre ne la désigne.
            $listing->photos()->sync(
                collect($row['photos'])
                    ->mapWithKeys(fn (string $key, int $i) => [
                        $photos[$key] ?? throw new \RuntimeException(
                            "Photo inconnue « {$key} » sur l'annonce « {$row['slug']} »."
                        ) => ['position' => $i],
                    ])
                    ->all()
            );
        }
    }

    /**
     * Les équipements mis en avant sont aussi des équipements possédés :
     * inutile de les répéter dans les deux listes, l'union se fait ici.
     *
     * @param  array<string, mixed>  $row
     * @return array<int, array{highlight: bool, note: string|null}>
     */
    private function pivot(array $row, Collection $amenities): array
    {
        $notes = $row['notes'] ?? [];
        $keys = array_unique([...$row['highlights'], ...$row['amenities']]);
        $pivot = [];

        foreach ($keys as $key) {
            $id = $amenities[$key] ?? throw new \RuntimeException(
                "Équipement inconnu « {$key} » sur l'annonce « {$row['slug']} »."
            );

            $pivot[$id] = [
                'highlight' => in_array($key, $row['highlights'], true),
                'note' => $notes[$key] ?? null,
            ];
        }

        return $pivot;
    }

    /** @return array<int, array<string, mixed>> */
    private function listings(): array
    {
        return [
            [
                'slug' => 'villa-ambatoloaka',
                'title' => 'Villa vue lagon, Ambatoloaka',
                'destination' => 'nosy-be',
                'scene' => 'lagoon',
                'photos' => ['an-palmier-iranja', 'an-plage-ampasipohy', 'an-ambatoloaka-village',
                    'an-banc-sable-iranja', 'an-village-ampasipohy', 'an-ampasindava-hotel'],
                'kind' => 'villa',
                'summary' => 'Trois chambres au-dessus du lagon, piscine et groupe électrogène.',
                'description' => "La villa domine la baie d'Ambatoloaka depuis un terrain clos planté de manguiers. Trois chambres, chacune avec sa salle d'eau, une grande varangue couverte pour les repas et une piscine qui reste à l'ombre l'après-midi. Le groupe électrogène prend le relais automatiquement pendant les coupures, et la réserve d'eau sur le toit couvre une journée complète. La plage se rejoint à pied en dix minutes.",
                'guests' => 6, 'bedrooms' => 3, 'beds' => 4, 'bathrooms' => 3,
                'surface' => 180, 'price' => 185000, 'min_nights' => 2, 'trust' => 4,
                'check_in' => '15:00', 'check_out' => '11:00', 'events' => true,
                'tags' => ['mer', 'famille'],
                'featured' => true,
                'highlights' => ['vue-mer', 'wifi-fibre', 'groupe-electrogene'],
                'notes' => [
                    'groupe-electrogene' => 'Démarrage automatique, sans coupure perceptible',
                    'piscine-privee' => '8 × 4 m, non chauffée',
                    'reserve-eau' => '2 000 litres, une journée d\'autonomie',
                ],
                'amenities' => [
                    'wifi-fibre', 'climatisation', 'ventilateur', 'moustiquaires', 'eau-chaude',
                    'linge-maison', 'serviettes', 'espace-travail',
                    'lit-queen', 'lits-simples', 'moustiquaire-lit', 'armoire', 'rangement-bagages',
                    'cuisine-equipee', 'refrigerateur', 'congelateur', 'gaziniere', 'four',
                    'micro-ondes', 'bouilloire', 'cafetiere', 'vaisselle', 'filtre-eau', 'table-repas',
                    'salle-bain-privee', 'douche', 'wc-separe', 'chauffe-eau-solaire', 'seche-cheveux',
                    'piscine-privee', 'jardin', 'terrain-clos', 'terrasse', 'veranda', 'paillote',
                    'barbecue', 'mobilier-jardin', 'douche-exterieure', 'hamac',
                    'electricite-jirama', 'panneaux-solaires', 'eau-courante', 'reserve-eau',
                    'prises-europeennes',
                    'gardien-24h', 'portail-securise', 'coffre-fort', 'extincteur',
                    'trousse-secours', 'eclairage-exterieur',
                    'menage-quotidien', 'petit-dejeuner', 'cuisinier', 'transfert-aeroport',
                    'television', 'enceinte', 'masques-tuba', 'kayak',
                    'parking-cour', 'route-bitumee',
                ],
            ],
            [
                'slug' => 'bungalow-madirokely',
                'title' => 'Bungalow pieds dans l\'eau, Madirokely',
                'destination' => 'nosy-be',
                'scene' => 'beach',
                'photos' => ['an-case-nosy-komba', 'an-pirogues-iranja', 'an-ambatoloaka-rue',
                    'an-village-nosy-komba', 'an-boutre-antanimora', 'an-nosy-komba-hotel'],
                'kind' => 'bungalow',
                'summary' => 'Une chambre ouverte sur le sable, petit-déjeuner servi sous la paillote.',
                'description' => "Bungalow en bois posé à quinze mètres de l'eau, sur la plage de Madirokely. Une chambre avec moustiquaire, une paillote pour manger dehors, une douche extérieure pour rentrer de la mer. Pas de climatisation : la brise du canal suffit la nuit, et le ventilateur prend le relais. L'électricité vient de panneaux solaires, l'eau d'un forage — les coupures de la ville n'atteignent pas le bungalow.",
                'guests' => 2, 'bedrooms' => 1, 'beds' => 1, 'bathrooms' => 1,
                'surface' => 45, 'price' => 95000, 'min_nights' => 2, 'trust' => 3,
                'check_in' => '14:00', 'check_out' => '10:00',
                'tags' => ['mer'],
                'featured' => false,
                'highlights' => ['acces-plage', 'petit-dejeuner'],
                'notes' => [
                    'acces-plage' => 'Quinze mètres de sable, sans route à traverser',
                    'petit-dejeuner' => 'Servi sous la paillote, fruits de la saison',
                ],
                'amenities' => [
                    'wifi', 'ventilateur', 'moustiquaires', 'eau-chaude', 'linge-maison', 'serviettes',
                    'lit-double', 'moustiquaire-lit', 'armoire',
                    'coin-cuisine', 'refrigerateur', 'rechaud-gaz', 'bouilloire', 'vaisselle', 'filtre-eau',
                    'salle-bain-privee', 'douche', 'chauffe-eau-solaire',
                    'vue-mer', 'terrasse', 'paillote', 'douche-exterieure', 'hamac',
                    'panneaux-solaires', 'puits-forage', 'reserve-eau',
                    'gardien-nuit', 'eclairage-exterieur',
                    'menage-quotidien', 'transfert-aeroport',
                    'masques-tuba',
                    'parking-cour', 'piste-4x4',
                ],
            ],
            [
                'slug' => 'maison-itasy',
                'title' => 'Maison au bord du lac Itasy',
                'destination' => 'ampefy',
                'scene' => 'lake',
                'photos' => ['an-villa-ampefy', 'an-itasy-lac', 'an-lac-ampefy',
                    'an-demeure-bois', 'ampefy-itasy'],
                'kind' => 'maison',
                'summary' => 'Quatre chambres, cheminée et ponton privé sur le lac.',
                'description' => "Maison familiale bâtie sur la rive, avec un ponton privé où une pirogue reste amarrée. Quatre chambres, une grande table pour dix, et une cheminée qui sert vraiment de juin à août — les nuits d'Ampefy descendent bas. Le terrain est clos et gardé, le groupe électrogène couvre l'éclairage et le réfrigérateur pendant les coupures.",
                'guests' => 8, 'bedrooms' => 4, 'beds' => 6, 'bathrooms' => 2,
                'surface' => 200, 'price' => 140000, 'min_nights' => 2, 'trust' => 4,
                'check_in' => '16:00', 'check_out' => '11:00', 'pets' => true, 'events' => true,
                'tags' => ['lac', 'famille'],
                'featured' => false,
                'highlights' => ['cheminee', 'table-repas', 'ponton'],
                'notes' => [
                    'ponton' => 'Pirogue amarrée, gilets fournis',
                    'table-repas' => 'Dix couverts, sous la véranda',
                    'groupe-electrogene' => 'Couvre l\'éclairage et le réfrigérateur',
                ],
                'amenities' => [
                    'wifi', 'ventilateur', 'chauffage', 'eau-chaude', 'linge-maison',
                    'serviettes', 'fer-repasser',
                    'lit-double', 'lits-simples', 'lits-superposes', 'canape-lit', 'armoire',
                    'cuisine-equipee', 'refrigerateur', 'congelateur', 'gaziniere', 'four',
                    'bouilloire', 'cafetiere', 'vaisselle', 'filtre-eau',
                    'salle-bain-privee', 'douche', 'baignoire', 'wc-separe',
                    'vue-lac', 'jardin', 'terrain-clos', 'terrasse', 'veranda', 'barbecue',
                    'mobilier-jardin',
                    'electricite-jirama', 'groupe-electrogene', 'eau-courante', 'puits-forage',
                    'reserve-eau',
                    'gardien-24h', 'portail-securise', 'detecteur-fumee', 'extincteur', 'trousse-secours',
                    'menage-inclus',
                    'television', 'livres-jeux', 'kayak', 'materiel-peche',
                    'parking-cour', 'route-bitumee',
                ],
            ],
            [
                'slug' => 'front-de-mer-amborovy',
                'title' => 'Appartement front de mer, Amborovy',
                'destination' => 'majunga',
                'scene' => 'sunset',
                'photos' => ['an-mahajanga-corniche', 'couchant-majunga', 'an-majunga-ville',
                    'an-majunga-bois-sacre'],
                'kind' => 'appartement',
                'summary' => 'Deux chambres climatisées, balcon plein ouest sur le canal.',
                'description' => "Appartement au troisième étage d'une résidence gardée d'Amborovy, avec un balcon qui prend le coucher de soleil de face. Deux chambres climatisées, un séjour ouvert sur la cuisine, un onduleur qui tient l'éclairage et le wifi pendant les coupures. La plage du Grand Pavois est à cinq minutes à pied.",
                'guests' => 4, 'bedrooms' => 2, 'beds' => 3, 'bathrooms' => 1,
                'surface' => 85, 'price' => 120000, 'min_nights' => 2, 'trust' => 3,
                'check_in' => '14:00', 'check_out' => '11:00',
                'tags' => ['mer', 'ville'],
                'featured' => false,
                'highlights' => ['climatisation', 'balcon'],
                'notes' => [
                    'balcon' => 'Plein ouest, sur le canal du Mozambique',
                    'onduleur' => 'Tient l\'éclairage et le wifi pendant les coupures',
                ],
                'amenities' => [
                    'wifi', 'climatisation', 'ventilateur', 'moustiquaires', 'eau-chaude',
                    'linge-maison', 'serviettes', 'espace-travail',
                    'lit-double', 'lits-simples', 'canape-lit', 'armoire',
                    'cuisine-equipee', 'refrigerateur', 'gaziniere', 'four', 'micro-ondes',
                    'bouilloire', 'vaisselle', 'filtre-eau', 'table-repas',
                    'salle-bain-privee', 'douche', 'seche-cheveux',
                    'vue-mer',
                    'electricite-jirama', 'onduleur', 'eau-courante', 'reserve-eau', 'prises-europeennes',
                    'gardien-24h', 'portail-securise', 'extincteur',
                    'menage-inclus', 'arrivee-autonome',
                    'television',
                    'parking-cour', 'route-bitumee', 'ascenseur',
                ],
            ],
            [
                'slug' => 'villa-coloniale-antsirabe',
                'title' => 'Villa coloniale, centre d\'Antsirabe',
                'destination' => 'antsirabe',
                'scene' => 'highland',
                'photos' => ['an-thermes-front', 'an-thermes-jardin', 'an-antsirabe-rue',
                    'avenue-antsirabe'],
                'kind' => 'villa',
                'summary' => 'Cinq chambres chauffées, terrain clos et parking dans la cour.',
                'description' => "Villa des années trente à dix minutes à pied du marché, sur un terrain clos et arboré. Cinq chambres, trois salles d'eau, une cuisine dimensionnée pour recevoir. Le chauffage et la cheminée ne sont pas décoratifs : Antsirabe descend sous dix degrés de juin à août. Voiture avec chauffeur disponible sur demande.",
                'guests' => 10, 'bedrooms' => 5, 'beds' => 8, 'bathrooms' => 3,
                'surface' => 320, 'price' => 160000, 'min_nights' => 2, 'trust' => 2,
                'check_in' => '15:00', 'check_out' => '12:00', 'pets' => true, 'events' => true,
                'tags' => ['hautes-terres', 'ville', 'famille'],
                'featured' => false,
                'highlights' => ['chauffage', 'terrain-clos', 'parking-cour'],
                'notes' => [
                    'chauffage' => 'Radiateurs dans les cinq chambres',
                    'terrain-clos' => '1 200 m² arborés, portail sur rue',
                ],
                'amenities' => [
                    'wifi', 'chauffage', 'cheminee', 'eau-chaude', 'linge-maison', 'serviettes',
                    'fer-repasser', 'espace-travail',
                    'lit-double', 'lits-simples', 'lits-superposes', 'lit-bebe', 'armoire',
                    'rangement-bagages',
                    'cuisine-equipee', 'refrigerateur', 'congelateur', 'gaziniere', 'four',
                    'micro-ondes', 'bouilloire', 'cafetiere', 'grille-pain', 'vaisselle',
                    'filtre-eau', 'table-repas',
                    'salle-bain-privee', 'douche', 'baignoire', 'wc-separe',
                    'jardin', 'terrasse', 'veranda', 'barbecue', 'mobilier-jardin',
                    'electricite-jirama', 'groupe-electrogene', 'eau-courante', 'reserve-eau',
                    'gardien-24h', 'portail-securise', 'detecteur-fumee', 'extincteur',
                    'trousse-secours', 'eclairage-exterieur',
                    'menage-inclus', 'blanchisserie', 'voiture-chauffeur',
                    'television', 'livres-jeux', 'velos',
                    'route-bitumee',
                ],
            ],
            [
                'slug' => 'case-ifaty',
                'title' => 'Case sur le sable, Ifaty',
                'destination' => 'tulear',
                'scene' => 'beach',
                'photos' => ['an-plage-ifaty', 'an-ifaty-couchant', 'pirogue-ifaty'],
                'kind' => 'bungalow',
                'summary' => 'Une case simple face au récif, solaire et douche extérieure.',
                'description' => "Case en bois et falafa, sans électricité de ville ni salle de bain privative : ce qu'elle offre, c'est le récif à deux cents mètres et rien entre elle et la mer. Panneaux solaires pour l'éclairage, forage pour l'eau, douche extérieure. La sortie sur le récif se réserve la veille auprès des pêcheurs du village. L'accès depuis la route d'Ifaty se fait en 4×4.",
                'guests' => 3, 'bedrooms' => 1, 'beds' => 2, 'bathrooms' => 1,
                'surface' => 35, 'price' => 78000, 'min_nights' => 1, 'trust' => 1,
                'check_in' => '12:00', 'check_out' => '10:00', 'pets' => true,
                'tags' => ['mer'],
                'featured' => false,
                'highlights' => ['douche-exterieure', 'sortie-recif'],
                'notes' => [
                    'sortie-recif' => 'En pirogue à balancier, à réserver la veille',
                    'salle-bain-partagee' => 'Commune aux trois cases du terrain',
                    'piste-4x4' => 'Deux kilomètres de sable depuis la route d\'Ifaty',
                ],
                'amenities' => [
                    'ventilateur', 'moustiquaires', 'linge-maison',
                    'lit-double', 'lits-simples', 'moustiquaire-lit',
                    'coin-cuisine', 'refrigerateur', 'rechaud-gaz', 'vaisselle', 'filtre-eau',
                    'salle-bain-partagee', 'douche',
                    'acces-plage', 'vue-mer', 'paillote', 'hamac',
                    'panneaux-solaires', 'puits-forage', 'reserve-eau',
                    'gardien-nuit',
                    'masques-tuba', 'materiel-peche', 'kayak',
                    'parking-cour', 'piste-4x4',
                    'plain-pied',
                ],
            ],
            [
                'slug' => 'lodge-andasibe',
                'title' => 'Lodge en lisière de forêt',
                'destination' => 'andasibe',
                'scene' => 'forest',
                'photos' => ['an-riviere-vohimana', 'an-andasibe-parc', 'passerelle-andasibe',
                    'an-riviere-andasibe', 'an-indri-andasibe', 'an-maria-lodge-jardin'],
                'kind' => 'lodge',
                'summary' => 'Deux chambres sur pilotis, guide local et terrasse sur la canopée.',
                'description' => "Lodge sur pilotis en lisière du corridor forestier, à vingt minutes de la réserve. Deux chambres, une terrasse d'où l'on entend les indris au lever du jour, et un guide du village qui accompagne les sorties du matin. Autonome en énergie : solaire, groupe électrogène en secours, onduleur pour la nuit. Il fait frais ici, le chauffage sert.",
                'guests' => 4, 'bedrooms' => 2, 'beds' => 3, 'bathrooms' => 2,
                'surface' => 90, 'price' => 175000, 'min_nights' => 2, 'trust' => 4,
                'check_in' => '15:00', 'check_out' => '10:00',
                'tags' => ['foret'],
                'featured' => false,
                'highlights' => ['terrasse', 'guide-local'],
                'notes' => [
                    'terrasse' => 'Sur pilotis, ouverte sur la canopée',
                    'guide-local' => 'Sorties du matin dans la réserve, inclus',
                ],
                'amenities' => [
                    'wifi', 'ventilateur', 'moustiquaires', 'chauffage', 'eau-chaude',
                    'linge-maison', 'serviettes',
                    'lit-double', 'lits-simples', 'moustiquaire-lit', 'armoire',
                    'coin-cuisine', 'refrigerateur', 'rechaud-gaz', 'bouilloire', 'cafetiere',
                    'vaisselle', 'filtre-eau', 'table-repas',
                    'salle-bain-privee', 'douche', 'chauffe-eau-solaire',
                    'vue-montagne', 'jardin', 'veranda', 'mobilier-jardin', 'hamac',
                    'groupe-electrogene', 'panneaux-solaires', 'onduleur', 'puits-forage', 'reserve-eau',
                    'gardien-24h', 'detecteur-fumee', 'extincteur', 'trousse-secours',
                    'eclairage-exterieur',
                    'menage-quotidien', 'petit-dejeuner', 'cuisinier', 'transfert-aeroport',
                    'livres-jeux',
                    'parking-cour', 'piste-4x4',
                ],
            ],
            [
                'slug' => 'studio-thermal',
                'title' => 'Studio design, quartier thermal',
                'destination' => 'antsirabe',
                'scene' => 'highland',
                'photos' => ['an-salon-emyrne', 'an-thermes-jardin', 'an-antsirabe-rue',
                    'avenue-antsirabe'],
                'kind' => 'studio',
                'summary' => 'Un studio de plain-pied, fibre et bureau, gardé jour et nuit.',
                'description' => 'Studio rénové dans une petite résidence du quartier thermal, pensé pour rester plusieurs semaines : bureau face à la fenêtre, fibre, kitchenette complète et blanchisserie sur place. Gardien jour et nuit, arrivée autonome par boîte à clés. De plain-pied, sans marche depuis la rue.',
                'guests' => 2, 'bedrooms' => 1, 'beds' => 1, 'bathrooms' => 1,
                'surface' => 32, 'price' => 70000, 'min_nights' => 3, 'trust' => 3,
                'check_in' => '14:00', 'check_out' => '11:00', 'max_nights' => 90,
                'tags' => ['ville', 'hautes-terres'],
                'featured' => false,
                'highlights' => ['wifi-fibre', 'espace-travail', 'gardien-24h'],
                'notes' => [
                    'espace-travail' => 'Bureau et chaise de travail, face à la fenêtre',
                    'arrivee-autonome' => 'Boîte à clés, code envoyé la veille',
                ],
                'amenities' => [
                    'chauffage', 'eau-chaude', 'linge-maison', 'serviettes', 'fer-repasser',
                    'lit-queen', 'armoire', 'rangement-bagages',
                    'coin-cuisine', 'refrigerateur', 'rechaud-gaz', 'micro-ondes', 'bouilloire',
                    'cafetiere', 'grille-pain', 'vaisselle', 'filtre-eau', 'table-repas',
                    'salle-bain-privee', 'douche', 'seche-cheveux', 'produits-toilette',
                    'balcon',
                    'electricite-jirama', 'onduleur', 'eau-courante', 'reserve-eau', 'prises-europeennes',
                    'portail-securise', 'detecteur-fumee', 'extincteur',
                    'menage-inclus', 'arrivee-autonome', 'blanchisserie',
                    'television', 'enceinte',
                    'parking-rue', 'route-bitumee',
                    'plain-pied', 'douche-plain-pied',
                ],
            ],
        ];
    }
}
