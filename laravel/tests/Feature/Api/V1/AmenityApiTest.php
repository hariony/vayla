<?php

namespace Tests\Feature\Api\V1;

use App\Enums\AmenityGroup;
use App\Models\Amenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmenityApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_le_vocabulaire_est_publie_groupe(): void
    {
        $data = $this->getJson('/api/v1/amenities')->assertOk()->json('data');

        // Toutes les rubriques de l'enum sont servies, dans son ordre.
        $this->assertSame(
            array_map(fn (AmenityGroup $g) => $g->value, AmenityGroup::ordered()),
            array_column($data, 'key')
        );

        $this->assertSame('Essentiels', $data[0]['label']);
        $this->assertNotEmpty($data[0]['amenities']);
    }

    public function test_seule_la_rubrique_energie_porte_une_note(): void
    {
        $notes = collect($this->getJson('/api/v1/amenities')->json('data'))
            ->filter(fn (array $g) => $g['note'] !== null)
            ->pluck('key')
            ->all();

        // Le délestage et l'eau ne se devinent pas : c'est la seule rubrique
        // qui a besoin d'être expliquée, avec l'accessibilité.
        $this->assertSame(['energie', 'accessibilite'], $notes);
    }

    public function test_les_filtres_sont_un_sous_ensemble_du_vocabulaire(): void
    {
        $tout = collect($this->getJson('/api/v1/amenities')->json('data'))
            ->flatMap(fn (array $g) => array_column($g['amenities'], 'key'));

        $filtres = collect($this->getJson('/api/v1/amenities/filters')->json('data'))
            ->flatMap(fn (array $g) => array_column($g['amenities'], 'key'));

        $this->assertNotEmpty($filtres);
        $this->assertLessThan($tout->count(), $filtres->count());
        $this->assertEmpty($filtres->diff($tout), 'Un filtre porte sur un équipement absent du vocabulaire.');

        // Personne ne cherche un logement par grille-pain.
        $this->assertTrue($filtres->contains('groupe-electrogene'));
        $this->assertFalse($filtres->contains('grille-pain'));
    }

    public function test_les_cles_du_vocabulaire_sont_uniques(): void
    {
        $keys = Amenity::query()->pluck('key');

        $this->assertSame($keys->count(), $keys->unique()->count());
    }
}
