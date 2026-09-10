<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class BookingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'traveller' => 'Rakoto Andriamahefa',
            'traveller_phone' => '+261 34 12 345 67',
            'guests' => 2,
            'arrival' => Carbon::today()->addDays(200)->toDateString(),
            'departure' => Carbon::today()->addDays(203)->toDateString(),
            'message' => 'Arrivée vers 16 h.',
        ], $extra);
    }

    public function test_le_formulaire_sert_le_logement_et_le_calendrier(): void
    {
        $this->get('/logements/villa-ambatoloaka/reserver')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Bookings/Create')
                ->where('listing.slug', 'villa-ambatoloaka')
                ->has('calendar.blocked')
                ->has('rules')
                ->where('holdHours', 48)
            );
    }

    public function test_les_dates_de_la_fiche_sont_reprises(): void
    {
        // Le voyageur a choisi ses dates sur la fiche : il ne les ressaisit pas.
        $this->get('/logements/villa-ambatoloaka/reserver?arrivee=2027-05-10&depart=2027-05-14')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('prefill.arrival', '2027-05-10')
                ->where('prefill.departure', '2027-05-14')
            );
    }

    public function test_une_reservation_valide_mene_a_sa_reference(): void
    {
        $this->post('/logements/villa-ambatoloaka/reserver', $this->payload())
            ->assertRedirect();

        $booking = Booking::latest('id')->firstOrFail();

        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame(3, $booking->nights);
        $this->assertNotNull($booking->hold_expires_at);

        $this->get("/reservations/{$booking->reference}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Bookings/Confirmed')
                ->where('booking.reference', $booking->reference)
                ->where('booking.status', 'pending')
                ->has('booking.ownerName')
            );
    }

    public function test_aucun_champ_de_paiement_nest_demande(): void
    {
        // Vayla n'encaisse rien : le contrat de ce formulaire ne doit
        // contenir aucun champ financier, ni maintenant ni par dérive.
        $this->post('/logements/villa-ambatoloaka/reserver', $this->payload([
            'card_number' => '4111111111111111',
            'deposit' => 100000,
        ]))->assertRedirect();

        $booking = Booking::latest('id')->firstOrFail();

        $this->assertArrayNotHasKey('card_number', $booking->getAttributes());
        $this->assertArrayNotHasKey('deposit', $booking->getAttributes());
    }

    public function test_un_refus_metier_revient_dans_le_formulaire(): void
    {
        // Un chevauchement n'est pas une panne : il revient à côté des dates,
        // là où il se corrige — pas sur une page d'erreur.
        $this->post('/logements/villa-ambatoloaka/reserver', $this->payload());

        $this->post('/logements/villa-ambatoloaka/reserver', $this->payload([
            'arrival' => Carbon::today()->addDays(201)->toDateString(),
            'departure' => Carbon::today()->addDays(204)->toDateString(),
        ]))->assertSessionHasErrors('arrival');

        $this->assertSame(1, Booking::query()->where('is_demo', false)->count());
    }

    public function test_la_validation_refuse_une_date_passee(): void
    {
        $this->post('/logements/villa-ambatoloaka/reserver', $this->payload([
            'arrival' => Carbon::today()->subDays(3)->toDateString(),
            'departure' => Carbon::today()->addDay()->toDateString(),
        ]))->assertSessionHasErrors('arrival');

        $this->assertSame(0, Booking::query()->where('is_demo', false)->count());
    }

    public function test_une_reference_inconnue_repond_404(): void
    {
        $this->get('/reservations/VY-XXXXX')->assertNotFound();
    }
}
