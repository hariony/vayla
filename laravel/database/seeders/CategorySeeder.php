<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Le rail de filtres de l'accueil. Pictogrammes au trait, jamais de photos :
 * c'est un filtre, pas une galerie.
 *
 * « Tout » est une ligne comme les autres pour que l'ordre du rail vive
 * entièrement en base — cette position a vocation à être vendue.
 * « Séjour confirmé » filtre sur le niveau 4 de l'échelle de confiance et
 * n'est jamais rattaché à une annonce par le pivot : le niveau est la seule
 * source de vérité.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['key' => 'all',           'label' => 'Tout',            'icon' => 'sparkle'],
            ['key' => 'mer',           'label' => 'Bord de mer',     'icon' => 'wave'],
            ['key' => 'lac',           'label' => 'Lacs',            'icon' => 'drop'],
            ['key' => 'hautes-terres', 'label' => 'Hautes terres',   'icon' => 'peak'],
            ['key' => 'foret',         'label' => 'Forêt',           'icon' => 'leaf'],
            ['key' => 'ville',         'label' => 'En ville',        'icon' => 'city'],
            ['key' => 'famille',       'label' => 'Grandes tablées', 'icon' => 'group'],
            ['key' => 'verifie',       'label' => 'Séjour confirmé', 'icon' => 'check'],
        ];

        foreach ($categories as $position => $category) {
            Category::updateOrCreate(
                ['key' => $category['key']],
                $category + ['position' => $position]
            );
        }
    }
}
