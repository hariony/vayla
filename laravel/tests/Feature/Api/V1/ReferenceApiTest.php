<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lechelle_de_confiance_est_publiee(): void
    {
        $this->getJson('/api/v1/trust-levels')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.level', 1)
            ->assertJsonPath('data.0.key', 'declared')
            ->assertJsonPath('data.3.key', 'proven')
            ->assertJsonPath('data.3.name', 'Séjour confirmé');
    }

    public function test_les_destinations_et_les_categories_sont_publiees(): void
    {
        $this->seed();

        $this->getJson('/api/v1/destinations')->assertOk()->assertJsonCount(11, 'data');
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(8, 'data');
        $this->getJson('/api/v1/destinations/nosy-be')->assertOk()->assertJsonPath('data.name', 'Nosy Be');
        $this->getJson('/api/v1/destinations/nulle-part')->assertNotFound();
    }
}
