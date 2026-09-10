<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Unavailability;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function listing(): Listing
    {
        return Listing::with('unavailabilities')->where('slug', 'case-ifaty')->firstOrFail();
    }

    public function test_le_calendrier_ne_sert_que_des_periodes_a_venir(): void
    {
        $listing = $this->listing();

        // Une période entièrement passée n'a rien à faire dans le calendrier.
        Unavailability::create([
            'listing_id' => $listing->id,
            'starts_on' => Carbon::today()->subDays(40)->toDateString(),
            'ends_on' => Carbon::today()->subDays(35)->toDateString(),
        ]);

        $blocked = app(AvailabilityService::class)->blocked($listing->fresh('unavailabilities'));

        foreach ($blocked as $p) {
            $this->assertGreaterThanOrEqual(Carbon::today()->toDateString(), $p['to']);
        }
    }

    public function test_une_periode_a_cheval_sur_aujourdhui_est_tronquee(): void
    {
        $listing = $this->listing();

        Unavailability::create([
            'listing_id' => $listing->id,
            'starts_on' => Carbon::today()->subDays(3)->toDateString(),
            'ends_on' => Carbon::today()->addDays(3)->toDateString(),
        ]);

        $blocked = collect(app(AvailabilityService::class)->blocked($listing->fresh('unavailabilities')));
        $aujourdhui = Carbon::today()->toDateString();

        // Le passé n'occupe pas le calendrier : la période commence aujourd'hui.
        $this->assertTrue(
            $blocked->contains(fn (array $p) => $p['from'] === $aujourdhui),
            'La période à cheval devrait être tronquée à aujourd\'hui.'
        );
    }

    public function test_lhorizon_est_borne_a_douze_mois(): void
    {
        $listing = $this->listing();

        Unavailability::create([
            'listing_id' => $listing->id,
            'starts_on' => Carbon::today()->addYears(3)->toDateString(),
            'ends_on' => Carbon::today()->addYears(3)->addDays(5)->toDateString(),
        ]);

        $calendrier = app(AvailabilityService::class)->calendar($listing->fresh('unavailabilities'));

        foreach ($calendrier['blocked'] as $p) {
            $this->assertLessThanOrEqual($calendrier['to'], $p['from']);
        }
    }

    public function test_la_fiche_publie_le_calendrier_et_le_reglement(): void
    {
        $data = $this->getJson('/api/v1/listings/studio-thermal')->assertOk()->json('data');

        $this->assertArrayHasKey('calendar', $data);
        $this->assertArrayHasKey('rules', $data);

        $this->assertSame(3, $data['calendar']['minNights']);
        $this->assertSame(90, $data['calendar']['maxNights']);
        $this->assertSame(70000, $data['calendar']['price']);
        $this->assertNotEmpty($data['calendar']['blocked']);

        $this->assertSame('14:00', $data['rules']['checkInFrom']);
        $this->assertSame('11:00', $data['rules']['checkOutBefore']);
        $this->assertFalse($data['rules']['pets']);
    }

    public function test_chaque_periode_a_une_fin_apres_son_debut(): void
    {
        // `ends_on` est la dernière nuit occupée : une période inversée
        // masquerait des nuits au lieu d'en bloquer.
        foreach (Unavailability::all() as $p) {
            $this->assertTrue(
                $p->ends_on->greaterThanOrEqualTo($p->starts_on),
                "Période inversée sur l'annonce {$p->listing_id}."
            );
        }
    }

    public function test_les_periodes_semees_sont_toutes_a_venir(): void
    {
        // Les dates du seeder sont relatives à aujourd'hui : semées en dur,
        // elles seraient toutes dans le passé six mois plus tard et le
        // calendrier de démonstration afficherait un logement libre à l'année.
        $this->assertGreaterThan(0, Unavailability::query()->count());

        foreach (Unavailability::all() as $p) {
            $this->assertTrue(
                $p->ends_on->greaterThanOrEqualTo(Carbon::today()),
                'Le seeder a produit une période entièrement passée.'
            );
        }
    }
}
