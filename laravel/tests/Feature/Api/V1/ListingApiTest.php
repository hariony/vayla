<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_la_liste_est_paginee_et_annonce_le_mode_demo(): void
    {
        $this->getJson('/api/v1/listings')
            ->assertOk()
            ->assertJsonCount(8, 'data')
            ->assertJsonPath('meta.total', 8)
            ->assertJsonPath('meta.demo', true)
            ->assertJsonStructure([
                'data' => [['slug', 'title', 'destination', 'place', 'region', 'photo', 'guests', 'price', 'trust', 'tags', 'perks', 'featured']],
                'meta' => ['page', 'per_page', 'total', 'pages', 'demo'],
            ]);
    }

    public function test_le_filtre_par_destination(): void
    {
        $this->getJson('/api/v1/listings?destination=nosy-be')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * « Séjour confirmé » n'est pas une étiquette stockée : c'est le niveau 4
     * de l'échelle. Le filtre doit donc rendre exactement les annonces de
     * niveau 4, et ListingData doit leur remettre le tag.
     */
    public function test_la_categorie_verifie_se_deduit_du_niveau_quatre(): void
    {
        $response = $this->getJson('/api/v1/listings?category=verifie')->assertOk();

        $data = $response->json('data');

        $this->assertCount(3, $data);

        foreach ($data as $listing) {
            $this->assertSame(4, $listing['trust']);
            $this->assertContains('verifie', $listing['tags']);
        }
    }

    public function test_le_filtre_par_nombre_de_voyageurs(): void
    {
        $this->getJson('/api/v1/listings?guests=8')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_une_annonce_inconnue_repond_404_en_json(): void
    {
        $this->getJson('/api/v1/listings/nexiste-pas')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_la_pagination_est_bornee(): void
    {
        $this->getJson('/api/v1/listings?per_page=9999')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_hors_mode_demo_lapi_ne_sert_aucune_annonce_fictive(): void
    {
        config(['vayla.demo' => false]);

        $this->getJson('/api/v1/listings')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.demo', false);
    }
}
