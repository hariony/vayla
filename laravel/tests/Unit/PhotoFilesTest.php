<?php

namespace Tests\Unit;

use Database\Seeders\PhotoSeeder;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Le catalogue de photos et les fichiers du disque doivent coïncider.
 *
 * Une clé sans fichier donne un cadre gris sur la fiche, et un fichier sans
 * clé est une image affichée nulle part mais créditée quand même. Les deux
 * sont passés inaperçus jusqu'ici parce que rien ne les vérifiait.
 *
 * Depuis le passage en plusieurs résolutions, une troisième erreur est
 * possible et invisible à l'œil : `width` annonce un palier que le disque ne
 * porte pas. Le navigateur choisit alors une image absente et n'affiche rien
 * — sur les seuls écrans qui la demandaient, donc pas sur celui du
 * développeur.
 */
class PhotoFilesTest extends TestCase
{
    private const PALIERS = [800, 1600, 3200];

    /** @return array<int, array<string, mixed>> */
    private function photos(): array
    {
        $method = new ReflectionMethod(PhotoSeeder::class, 'photos');

        return $method->invoke(new PhotoSeeder);
    }

    public function test_chaque_photo_declaree_a_ses_fichiers(): void
    {
        foreach ($this->photos() as $photo) {
            foreach (self::PALIERS as $palier) {
                if ($palier > $photo['width']) {
                    continue;
                }

                $this->assertFileExists(
                    public_path("images/lieux/{$photo['key']}-{$palier}.webp"),
                    "La photo « {$photo['key']} » annonce {$photo['width']} px mais le fichier {$palier} px manque."
                );
            }
        }
    }

    public function test_aucun_fichier_orphelin(): void
    {
        $declarees = array_column($this->photos(), 'key');

        foreach (glob(public_path('images/lieux/*.webp')) as $fichier) {
            $key = preg_replace('/-\d+$/', '', basename($fichier, '.webp'));

            $this->assertContains(
                $key,
                $declarees,
                'Le fichier « '.basename($fichier)." » n'est déclaré par aucun crédit."
            );
        }
    }

    /**
     * `width` est la largeur du plus grand fichier, pas une intention : si
     * elle dépasse ce qui existe, le `srcset` promet des pixels absents.
     */
    public function test_la_largeur_annoncee_est_celle_du_disque(): void
    {
        foreach ($this->photos() as $photo) {
            $this->assertContains($photo['width'], self::PALIERS,
                "Largeur hors paliers pour « {$photo['key']} » : {$photo['width']}.");

            $suivant = array_search($photo['width'], self::PALIERS, true) + 1;

            if (isset(self::PALIERS[$suivant])) {
                $this->assertFileDoesNotExist(
                    public_path("images/lieux/{$photo['key']}-".self::PALIERS[$suivant].'.webp'),
                    "« {$photo['key']} » porte un fichier plus grand que la largeur déclarée."
                );
            }
        }
    }

    public function test_chaque_photo_est_attribuable(): void
    {
        foreach ($this->photos() as $photo) {
            // Une photo sans auteur ne peut pas être créditée, donc pas
            // publiée : CC BY et CC BY-SA l'exigent.
            $this->assertNotEmpty($photo['author'], "Photo sans auteur : {$photo['key']}");
            $this->assertNotEmpty($photo['licence'], "Photo sans licence : {$photo['key']}");

            if ($photo['is_ai'] ?? false) {
                continue;   // une image générée n'a pas de page source
            }

            $this->assertNotEmpty($photo['source_url'], "Photo sans source : {$photo['key']}");
        }
    }

    /**
     * Une image générée doit se déclarer comme telle, et se reconnaître à sa clé.
     *
     * Vayla ne vend qu'une chose, la vérification. Une image fabriquée qui
     * passerait pour une prise de vue détruirait l'argument entier — et le
     * bandeau « Aperçu » du haut de fiche ne couvre pas ce cas : il parle de
     * l'annonce, pas de l'image. Les deux moitiés de la règle sont vérifiées
     * dans les deux sens : pas de clé `ia-` sans `is_ai`, pas de `is_ai` sans
     * clé `ia-`.
     */
    public function test_une_image_generee_se_declare(): void
    {
        foreach ($this->photos() as $photo) {
            $genereePorteeParLaCle = str_starts_with($photo['key'], 'ia-');
            $declaree = (bool) ($photo['is_ai'] ?? false);

            $this->assertSame($genereePorteeParLaCle, $declaree,
                "« {$photo['key']} » : la clé et le marquage `is_ai` se contredisent.");

            if ($declaree) {
                $this->assertNull($photo['source_url'] ?? null,
                    "« {$photo['key']} » est générée : elle ne peut pas avoir de page source.");
            }
        }
    }
}
