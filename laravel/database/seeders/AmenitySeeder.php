<?php

namespace Database\Seeders;

use App\Enums\AmenityGroup;
use App\Models\Amenity;
use Illuminate\Database\Seeder;

/**
 * Le vocabulaire des équipements.
 *
 * Il est écrit pour Madagascar, pas traduit d'une plateforme du Nord. Trois
 * rubriques n'existent nulle part ailleurs et sont pourtant celles qui
 * décident d'un séjour ici :
 *
 * - « Énergie et eau » : le délestage est quotidien, un groupe électrogène
 *   ou un forage n'est pas un luxe mais la différence entre une maison
 *   habitable et une maison vide à 19 h.
 * - « Moustiquaires » : sur les côtes, c'est une question de santé. Elle est
 *   déclarée deux fois — aux ouvertures et au-dessus du lit — parce que les
 *   deux ne se valent pas.
 * - « Accès en 4×4 nécessaire » : se dit AVANT la réservation, pas à
 *   l'arrivée du voyageur devant une piste.
 *
 * L'ordre de déclaration fait la position d'affichage. `filterable` marque
 * les équipements qui méritent une case dans la recherche : personne ne
 * cherche par grille-pain.
 */
class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        // **Créer ce qui manque, ne jamais réécrire.** Depuis que le
        // back-office édite ce référentiel, la base fait foi : un `make seed`
        // qui rétablirait les libellés d'origine effacerait en silence le
        // travail de l'équipe.
        foreach ($this->amenities() as $group => $rows) {
            foreach (array_values($rows) as $position => $row) {
                Amenity::firstOrCreate(
                    ['key' => $row[0]],
                    [
                        'label' => $row[1],
                        'group' => $group,
                        'icon' => $row[2],
                        'position' => $position,
                        'filterable' => $row[3] ?? false,
                    ]
                );
            }
        }
    }

    /**
     * @return array<string, array<int, array{0: string, 1: string, 2: string, 3?: bool}>>
     */
    private function amenities(): array
    {
        return [
            AmenityGroup::Essentiels->value => [
                ['wifi', 'Wifi', 'wifi', true],
                ['wifi-fibre', 'Wifi fibre', 'wifi', true],
                ['climatisation', 'Climatisation', 'snow', true],
                ['ventilateur', 'Ventilateur de plafond', 'fan'],
                ['moustiquaires', 'Moustiquaires aux ouvertures', 'net', true],
                ['chauffage', 'Chauffage', 'flame'],
                ['cheminee', 'Cheminée', 'flame'],
                ['eau-chaude', 'Eau chaude', 'drop', true],
                ['linge-maison', 'Draps et linge de maison fournis', 'towel'],
                ['serviettes', 'Serviettes de toilette fournies', 'towel'],
                ['fer-repasser', 'Fer et table à repasser', 'iron'],
                ['espace-travail', 'Espace de travail dédié', 'desk', true],
            ],

            AmenityGroup::Couchage->value => [
                ['lit-double', 'Lit double', 'bed'],
                ['lit-queen', 'Lit queen size', 'bed'],
                ['lits-simples', 'Lits simples', 'bed'],
                ['lits-superposes', 'Lits superposés', 'bed'],
                ['canape-lit', 'Canapé-lit', 'sofa'],
                ['lit-bebe', 'Lit bébé', 'baby', true],
                ['moustiquaire-lit', 'Moustiquaire au-dessus du lit', 'net'],
                ['armoire', 'Armoire ou penderie', 'wardrobe'],
                ['rangement-bagages', 'Rangement pour les bagages', 'wardrobe'],
            ],

            AmenityGroup::Cuisine->value => [
                ['cuisine-equipee', 'Cuisine entièrement équipée', 'pot', true],
                ['coin-cuisine', 'Coin cuisine', 'pot'],
                ['refrigerateur', 'Réfrigérateur', 'fridge'],
                ['congelateur', 'Congélateur', 'freezer'],
                ['gaziniere', 'Gazinière', 'gas'],
                ['rechaud-gaz', 'Réchaud à gaz', 'gas'],
                ['four', 'Four', 'oven'],
                ['micro-ondes', 'Micro-ondes', 'microwave'],
                ['bouilloire', 'Bouilloire', 'kettle'],
                ['cafetiere', 'Cafetière', 'coffee'],
                ['grille-pain', 'Grille-pain', 'toaster'],
                ['vaisselle', 'Vaisselle et ustensiles', 'plate'],
                ['filtre-eau', 'Filtre à eau potable', 'filter', true],
                ['table-repas', 'Table à manger', 'table'],
            ],

            AmenityGroup::SalleEau->value => [
                ['salle-bain-privee', 'Salle de bain privative', 'bath', true],
                ['salle-bain-partagee', 'Salle de bain partagée', 'bath'],
                ['douche', 'Douche', 'shower'],
                ['baignoire', 'Baignoire', 'bath'],
                ['wc-separe', 'WC séparés', 'toilet'],
                ['chauffe-eau-solaire', 'Chauffe-eau solaire', 'sun'],
                ['seche-cheveux', 'Sèche-cheveux', 'hair'],
                ['produits-toilette', 'Savon et produits de toilette', 'drop'],
            ],

            AmenityGroup::Exterieur->value => [
                ['piscine-privee', 'Piscine privée', 'pool', true],
                ['piscine-partagee', 'Piscine partagée', 'pool', true],
                ['acces-plage', 'Accès direct à la plage', 'wave', true],
                ['vue-mer', 'Vue mer', 'wave', true],
                ['vue-lac', 'Vue lac', 'lake'],
                ['vue-montagne', 'Vue montagne', 'mountain'],
                ['jardin', 'Jardin', 'tree'],
                ['terrain-clos', 'Terrain clôturé', 'fence'],
                ['terrasse', 'Terrasse', 'deck'],
                ['balcon', 'Balcon', 'deck'],
                ['veranda', 'Varangue couverte', 'deck'],
                ['paillote', 'Paillote ombragée', 'tree'],
                ['barbecue', 'Barbecue', 'grill'],
                ['mobilier-jardin', 'Mobilier de jardin', 'table'],
                ['douche-exterieure', 'Douche extérieure', 'shower'],
                ['hamac', 'Hamac', 'hammock'],
                ['ponton', 'Ponton privé', 'boat'],
            ],

            AmenityGroup::Energie->value => [
                ['electricite-jirama', 'Raccordement électrique JIRAMA', 'bolt'],
                ['groupe-electrogene', 'Groupe électrogène', 'bolt', true],
                ['panneaux-solaires', 'Panneaux solaires', 'sun', true],
                ['onduleur', 'Onduleur ou batterie de secours', 'bolt'],
                ['eau-courante', 'Eau courante JIRAMA', 'drop'],
                ['puits-forage', 'Puits ou forage', 'water', true],
                ['reserve-eau', 'Réserve d\'eau sur le toit', 'water'],
                ['prises-europeennes', 'Prises européennes 220 V', 'bolt'],
            ],

            AmenityGroup::Securite->value => [
                ['gardien-24h', 'Gardien 24 h/24', 'shield', true],
                ['gardien-nuit', 'Gardien de nuit', 'shield'],
                ['portail-securise', 'Portail sécurisé', 'gate'],
                ['coffre-fort', 'Coffre-fort', 'safe'],
                ['detecteur-fumee', 'Détecteur de fumée', 'smoke'],
                ['extincteur', 'Extincteur', 'extinguisher'],
                ['trousse-secours', 'Trousse de premiers secours', 'kit'],
                ['eclairage-exterieur', 'Éclairage extérieur', 'bolt'],
            ],

            AmenityGroup::Services->value => [
                ['menage-inclus', 'Ménage inclus', 'broom'],
                ['menage-quotidien', 'Ménage quotidien', 'broom'],
                ['petit-dejeuner', 'Petit-déjeuner', 'coffee', true],
                ['cuisinier', 'Cuisinier sur demande', 'chef'],
                ['blanchisserie', 'Blanchisserie', 'towel'],
                ['transfert-aeroport', 'Transfert aéroport', 'plane', true],
                ['guide-local', 'Guide local', 'guide'],
                ['voiture-chauffeur', 'Voiture avec chauffeur', 'car'],
                ['arrivee-autonome', 'Arrivée autonome', 'key'],
            ],

            AmenityGroup::Loisirs->value => [
                ['television', 'Télévision', 'tv'],
                ['enceinte', 'Enceinte bluetooth', 'speaker'],
                ['livres-jeux', 'Livres et jeux de société', 'book'],
                ['kayak', 'Kayak ou pirogue', 'kayak'],
                ['masques-tuba', 'Masques et tubas', 'mask'],
                ['sortie-recif', 'Sortie sur le récif', 'boat'],
                ['materiel-peche', 'Matériel de pêche', 'fish'],
                ['velos', 'Vélos', 'bike'],
            ],

            AmenityGroup::Acces->value => [
                ['parking-cour', 'Parking dans la cour', 'car', true],
                ['parking-rue', 'Stationnement dans la rue', 'car'],
                ['route-bitumee', 'Accès par route bitumée', 'road', true],
                ['piste-4x4', 'Accès en 4×4 nécessaire', 'road', true],
                ['ascenseur', 'Ascenseur', 'step'],
            ],

            AmenityGroup::Accessibilite->value => [
                ['plain-pied', 'Logement de plain-pied', 'step', true],
                ['acces-fauteuil', 'Accès en fauteuil roulant', 'wheel', true],
                ['douche-plain-pied', 'Douche sans marche', 'shower'],
                ['entree-large', 'Entrée large', 'step'],
            ],
        ];
    }
}
