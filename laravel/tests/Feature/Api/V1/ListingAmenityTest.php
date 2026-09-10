<?php

namespace Tests\Feature\Api\V1;

use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingAmenityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_deux_equipements_demandes_posent_deux_conditions(): void
    {
        $seul = $this->getJson('/api/v1/listings?amenities[]=groupe-electrogene')
            ->assertOk()->json('meta.total');

        $deux = $this->getJson('/api/v1/listings?amenities[]=groupe-electrogene&amenities[]=piscine-privee')
            ->assertOk()->json('meta.total');

        // Le filtre est conjonctif : ajouter une case ne peut que réduire.
        // Une disjonction ramènerait ici plus de logements, pas moins.
        $this->assertGreaterThan(1, $seul);
        $this->assertLessThan($seul, $deux);
    }

    public function test_un_equipement_inconnu_ne_ramene_rien(): void
    {
        $this->getJson('/api/v1/listings?amenities[]=heliport')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_la_liste_des_equipements_demandes_est_bornee(): void
    {
        $trop = collect(range(1, 21))->map(fn (int $i) => "amenities[]=x{$i}")->implode('&');

        $this->getJson("/api/v1/listings?{$trop}")->assertStatus(422);
    }

    public function test_le_filtre_par_type_et_par_prix(): void
    {
        $this->getJson('/api/v1/listings?kind=studio')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'studio-thermal');

        $this->getJson('/api/v1/listings?kind=chateau')->assertStatus(422);

        $prix = $this->getJson('/api/v1/listings?max_price=80000')->assertOk()->json('data');
        $this->assertNotEmpty($prix);
        foreach ($prix as $l) {
            $this->assertLessThanOrEqual(80000, $l['price']);
        }
    }

    public function test_la_carte_nannonce_que_des_equipements_que_la_fiche_detaille(): void
    {
        foreach (Listing::query()->pluck('slug') as $slug) {
            $fiche = $this->getJson("/api/v1/listings/{$slug}")->assertOk()->json('data');

            $tous = collect($fiche['amenities'])
                ->flatMap(fn (array $g) => array_column($g['amenities'], 'label'));

            // `perks` étant dérivé du pivot, une carte ne peut plus vanter un
            // équipement absent de la fiche — c'était le défaut du texte libre.
            foreach ($fiche['listing']['perks'] as $perk) {
                $this->assertTrue(
                    $tous->contains($perk),
                    "L'annonce « {$slug} » met en avant « {$perk} », absent de sa fiche."
                );
            }

            $this->assertSame($tous->count(), $fiche['listing']['amenityCount']);
        }
    }

    public function test_les_rubriques_vides_ne_sont_pas_servies(): void
    {
        $fiche = $this->getJson('/api/v1/listings/case-ifaty')->assertOk()->json('data');

        foreach ($fiche['amenities'] as $groupe) {
            $this->assertNotEmpty($groupe['amenities'], "Rubrique vide servie : {$groupe['key']}.");
        }

        // La case d'Ifaty n'a ni JIRAMA ni salle de bain privative, mais
        // elle a du solaire et un forage : la rubrique « Énergie et eau »
        // sort avec ce qu'elle a vraiment. Aucun service en revanche, donc
        // pas de titre « Services » suivi de rien.
        $rubriques = array_column($fiche['amenities'], 'key');
        $this->assertContains('energie', $rubriques);
        $this->assertNotContains('services', $rubriques);
    }

    public function test_la_couverture_est_la_premiere_photo_de_la_galerie(): void
    {
        foreach (Listing::query()->pluck('slug') as $slug) {
            $fiche = $this->getJson("/api/v1/listings/{$slug}")->assertOk()->json('data');

            $this->assertNotEmpty($fiche['gallery'], "L'annonce « {$slug} » n'a aucune photo.");

            // Aucune colonne ne désigne la couverture : elle EST la position 0.
            // Sans cette règle, une couverture pourrait ne pas être dans la
            // galerie — deux écritures pour un même fait.
            $this->assertSame(
                $fiche['gallery'][0]['key'],
                $fiche['listing']['photo'],
                "La couverture de « {$slug} » n'est pas la première photo de sa galerie."
            );
            $this->assertSame(count($fiche['gallery']), $fiche['listing']['photoCount']);
        }
    }

    public function test_chaque_photo_part_avec_son_credit(): void
    {
        $fiche = $this->getJson('/api/v1/listings/villa-ambatoloaka')->assertOk()->json('data');

        // CC BY et CC BY-SA exigent l'attribution partout où l'image est
        // affichée : la visionneuse plein écran doit pouvoir la lire.
        foreach ($fiche['gallery'] as $photo) {
            $this->assertNotEmpty($photo['caption'], "Photo sans légende : {$photo['key']}");
            $this->assertNotEmpty($photo['author'], "Photo sans auteur : {$photo['key']}");
            $this->assertNotEmpty($photo['licence'], "Photo sans licence : {$photo['key']}");
            $this->assertNotEmpty($photo['source'], "Photo sans source : {$photo['key']}");
        }
    }

    public function test_la_note_du_proprietaire_remonte_sur_lequipement(): void
    {
        $fiche = $this->getJson('/api/v1/listings/case-ifaty')->assertOk()->json('data');

        $acces = collect($fiche['amenities'])
            ->flatMap(fn (array $g) => $g['amenities'])
            ->firstWhere('key', 'piste-4x4');

        $this->assertNotNull($acces);
        $this->assertStringContainsString('sable', $acces['note']);
    }
}
