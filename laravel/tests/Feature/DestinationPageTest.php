<?php

namespace Tests\Feature;

use App\Enums\ClimateZone;
use App\Models\Destination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DestinationPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_latlas_liste_toutes_les_destinations(): void
    {
        $this->get('/destinations')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Destinations/Index')
                ->has('destinations', Destination::count())
            );
    }

    public function test_la_page_porte_la_saison_lacces_et_la_repartition(): void
    {
        $this->get('/destinations/nosy-be')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Destinations/Show')
                ->where('fiche.destination.name', 'Nosy Be')
                ->has('fiche.season.year', 12)
                ->has('fiche.trust', 4)
                ->where('fiche.access.airportCode', 'NOS')
                ->where('fiche.access.flight', '1 h 15')
            );
    }

    public function test_les_chiffres_sont_calcules_jamais_saisis(): void
    {
        $fiche = $this->get('/destinations/nosy-be')->assertOk()->viewData('page')['props']['fiche'];

        // Le compteur de la destination doit valoir la somme des barreaux :
        // deux sources qui divergeraient feraient mentir la page.
        $this->assertSame(
            $fiche['destination']['listings'],
            array_sum(array_column($fiche['trust'], 'count'))
        );
        $this->assertSame(count($fiche['listings']), $fiche['destination']['listings']);

        // Les bornes de prix viennent des annonces réelles.
        $prix = array_column($fiche['listings'], 'price');
        $this->assertSame(min($prix), $fiche['prices']['min']);
        $this->assertSame(max($prix), $fiche['prices']['max']);
    }

    public function test_une_destination_sans_annonce_ne_ment_pas(): void
    {
        // Cinq destinations sur onze n'ont aucune annonce : c'est le cas
        // majoritaire, il doit sortir proprement plutôt que d'être masqué.
        $fiche = $this->get('/destinations/morondava')->assertOk()->viewData('page')['props']['fiche'];

        $this->assertSame(0, $fiche['destination']['listings']);
        $this->assertEmpty($fiche['listings']);
        $this->assertNull($fiche['prices']['min']);

        // Les barreaux sortent quand même, tous à zéro : une échelle
        // amputée ne se lit plus comme une échelle.
        $this->assertCount(4, $fiche['trust']);
        $this->assertSame(0, array_sum(array_column($fiche['trust'], 'count')));
    }

    public function test_lacces_est_borne_a_ce_qui_est_declare(): void
    {
        // Ampefy n'a pas d'aéroport : rien ne doit être inventé pour
        // remplir le bloc.
        $access = $this->get('/destinations/ampefy')->assertOk()
            ->viewData('page')['props']['fiche']['access'];

        $this->assertNull($access['airportCode']);
        $this->assertNull($access['flight']);
        $this->assertSame('RN1', $access['route']);
        $this->assertSame(120, $access['km']);

        // Les durées routières varient du simple au double : la page le dit.
        $this->assertStringContainsString('indicatives', $access['caveat']);
    }

    public function test_antananarivo_na_ni_route_ni_vol_depuis_elle_meme(): void
    {
        $access = $this->get('/destinations/antananarivo')->assertOk()
            ->viewData('page')['props']['fiche']['access'];

        $this->assertNull($access['flight']);
        $this->assertNull($access['route']);
        $this->assertNotEmpty($access['note']);
    }

    public function test_chaque_destination_a_une_page_qui_repond(): void
    {
        foreach (Destination::pluck('slug') as $slug) {
            $this->get("/destinations/{$slug}")->assertOk();
        }
    }

    public function test_une_destination_inconnue_repond_404(): void
    {
        $this->get('/destinations/nulle-part')->assertNotFound();
    }

    public function test_la_saison_de_la_page_suit_la_facade_de_la_destination(): void
    {
        $fiche = $this->get('/destinations/andasibe')->assertOk()
            ->viewData('page')['props']['fiche'];

        $this->assertSame(ClimateZone::EstForet->label(), $fiche['season']['zone']);
    }
}
