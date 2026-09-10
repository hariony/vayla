<?php

namespace Tests\Feature;

use App\Enums\ClimateZone;
use App\Enums\SeasonKind;
use App\Models\Destination;
use App\Services\SeasonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le calendrier saisonnier est l'atout du produit : c'est la seule chose que
 * le calendrier de Vayla dit et qu'aucun autre ne dit. Il doit donc être
 * complet, et il doit dire les mauvaises nouvelles.
 */
class SeasonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_chaque_facade_couvre_les_douze_mois(): void
    {
        foreach (ClimateZone::ordered() as $zone) {
            $mois = $zone->months();

            $this->assertCount(12, $mois, "La façade « {$zone->value} » n'a pas douze mois.");

            foreach ($mois as $i => [$etat, $note]) {
                $this->assertNotNull(
                    SeasonKind::tryFrom($etat),
                    "État inconnu « {$etat} » au mois ".($i + 1)." de « {$zone->value} »."
                );
                $this->assertNotEmpty($note, "Mois sans précision : {$zone->value} #{$i}.");
            }
        }
    }

    public function test_chaque_facade_a_une_bonne_periode_et_une_mauvaise(): void
    {
        foreach (ClimateZone::ordered() as $zone) {
            $etats = collect($zone->months())->map(fn (array $m) => SeasonKind::from($m[0]));

            // Une façade où tout serait « meilleure période » ne renseignerait
            // rien : c'est le mauvais mois qui rend le bon crédible.
            $this->assertTrue(
                $etats->contains(fn (SeasonKind $k) => $k->isBest()),
                "La façade « {$zone->value} » n'a aucune bonne période."
            );
            $this->assertTrue(
                $etats->contains(fn (SeasonKind $k) => ! $k->isBest()),
                "La façade « {$zone->value} » n'a que des bonnes périodes."
            );
        }
    }

    public function test_chaque_destination_porte_une_facade(): void
    {
        foreach (Destination::all() as $destination) {
            $this->assertInstanceOf(
                ClimateZone::class,
                $destination->climate_zone,
                "La destination « {$destination->slug} » n'a pas de façade climatique."
            );
        }
    }

    public function test_andasibe_est_en_foret_pas_sur_la_cote(): void
    {
        // Andasibe est à trois heures de la mer : lui servir les baleines de
        // Sainte-Marie ferait dire au calendrier une chose fausse.
        $andasibe = Destination::where('slug', 'andasibe')->firstOrFail();
        $this->assertSame(ClimateZone::EstForet, $andasibe->climate_zone);

        $notes = collect($andasibe->climate_zone->months())->pluck(1)->implode(' ');
        $this->assertStringNotContainsString('baleine', mb_strtolower($notes));
    }

    public function test_la_fiche_publie_lannee_et_son_avertissement(): void
    {
        $saison = $this->getJson('/api/v1/listings/villa-ambatoloaka')
            ->assertOk()
            ->json('data.calendar.season');

        $this->assertCount(12, $saison['year']);
        $this->assertNotEmpty($saison['best']);
        $this->assertSame('Nord et Nosy Be', $saison['zone']);

        // Le lecteur doit savoir qu'il lit une tendance, pas une prévision.
        $this->assertStringContainsString('pas une prévision', $saison['caveat']);

        // Nosy Be en février : le calendrier doit le dire.
        $fevrier = collect($saison['year'])->firstWhere('n', 2);
        $this->assertTrue($fevrier['warning']);
        $this->assertSame('Risque cyclonique', $fevrier['label']);
    }

    public function test_les_meilleurs_mois_viennent_du_profil_jamais_saisis(): void
    {
        $sud = Destination::where('slug', 'tulear')->firstOrFail();
        $service = app(SeasonService::class);

        $attendus = collect($sud->climate_zone->months())
            ->filter(fn (array $m) => SeasonKind::from($m[0])->isBest())
            ->count();

        $this->assertCount($attendus, $service->bestMonths($sud));
    }
}
