<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Exceptions\BookingRefusedException;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\StayConfirmation;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function listing(string $slug = 'villa-ambatoloaka'): Listing
    {
        return Listing::with(['unavailabilities', 'bookings'])->where('slug', $slug)->firstOrFail();
    }

    /** Une fenêtre de N nuits sans aucune nuit déjà prise. */
    private function fenetre(Listing $listing, int $nuits = 3): Carbon
    {
        $prises = collect(app(AvailabilityService::class)->blocked($listing));

        for ($d = 1; $d < 300; $d++) {
            $depart = Carbon::today()->addDays($d);
            $libre = true;

            for ($i = 0; $i < $nuits; $i++) {
                $jour = $depart->copy()->addDays($i)->toDateString();
                if ($prises->contains(fn (array $p) => $jour >= $p['from'] && $jour <= $p['to'])) {
                    $libre = false;
                    break;
                }
            }

            if ($libre) {
                return $depart;
            }
        }

        $this->fail('Aucune fenêtre libre trouvée.');
    }

    private function reserve(Listing $listing, Carbon $arrivee, int $nuits = 3, array $extra = []): Booking
    {
        return app(BookingService::class)->book($listing, array_merge([
            'traveller' => 'Rakoto',
            'traveller_phone' => '+261 34 12 345 67',
            'guests' => 2,
            'arrival' => $arrivee->toDateString(),
            'departure' => $arrivee->copy()->addDays($nuits)->toDateString(),
        ], $extra));
    }

    public function test_une_reservation_retire_ses_nuits_du_calendrier(): void
    {
        $listing = $this->listing();
        $avant = count(app(AvailabilityService::class)->blocked($listing));

        $this->reserve($listing, $this->fenetre($listing));

        $apres = count(app(AvailabilityService::class)->blocked($this->listing()));
        $this->assertSame($avant + 1, $apres);
    }

    public function test_le_jour_du_depart_reste_reservable(): void
    {
        // Une nuit appartient à sa date d'arrivée : bloquer la date de départ
        // retirerait une nuit vendable à chaque réservation du calendrier.
        $listing = $this->listing();
        $arrivee = $this->fenetre($listing, 6);

        $this->reserve($listing, $arrivee, 3);

        $suivante = $this->reserve($this->listing(), $arrivee->copy()->addDays(3), 3);
        $this->assertSame(BookingStatus::Pending, $suivante->status);
    }

    public function test_un_chevauchement_est_refuse(): void
    {
        $listing = $this->listing();
        $arrivee = $this->fenetre($listing, 6);

        $this->reserve($listing, $arrivee, 4);

        $this->expectException(BookingRefusedException::class);
        $this->reserve($this->listing(), $arrivee->copy()->addDays(2), 3);
    }

    public function test_le_sejour_minimum_et_la_capacite_sont_opposables(): void
    {
        $listing = $this->listing();

        try {
            $this->reserve($listing, $this->fenetre($listing), 1);
            $this->fail('Une nuit sous le minimum aurait dû être refusée.');
        } catch (BookingRefusedException $e) {
            $this->assertStringContainsString('à partir de', $e->getMessage());
        }

        $this->expectException(BookingRefusedException::class);
        $this->reserve($this->listing(), $this->fenetre($listing), 3, ['guests' => 99]);
    }

    public function test_le_prix_et_le_taux_sont_figes_a_la_reservation(): void
    {
        $listing = $this->listing();
        $booking = $this->reserve($listing, $this->fenetre($listing), 3);

        $prixInitial = $booking->price_per_night;
        $totalInitial = $booking->total;

        // Le propriétaire réévalue son tarif, et la commission change : la
        // ligne de facture déjà engagée ne doit pas bouger d'un ariary.
        $listing->update(['price' => $listing->price * 2]);
        config(['vayla.commission.rate' => 0.20]);

        $booking->refresh();
        $this->assertSame($prixInitial, $booking->price_per_night);
        $this->assertSame($totalInitial, $booking->total);
        $this->assertSame(0.05, (float) $booking->commission_rate);
    }

    public function test_une_demande_sans_reponse_rend_ses_nuits(): void
    {
        $listing = $this->listing();
        $booking = $this->reserve($listing, $this->fenetre($listing), 3);

        $avecDemande = count(app(AvailabilityService::class)->blocked($this->listing()));

        // Le délai coule. Sans cette libération, un propriétaire distrait
        // verrait son calendrier se fermer tout seul.
        $booking->update(['hold_expires_at' => now()->subHour()]);
        $this->assertSame(1, app(BookingService::class)->releaseExpired());

        $booking->refresh();
        $this->assertSame(BookingStatus::Expired, $booking->status);
        $this->assertSame($avecDemande - 1, count(app(AvailabilityService::class)->blocked($this->listing())));
    }

    public function test_une_reservation_acceptee_ne_se_facture_pas(): void
    {
        $listing = $this->listing();
        $booking = app(BookingService::class)->accept($this->reserve($listing, $this->fenetre($listing), 3));

        // Facturer l'acceptation reviendrait à facturer les no-shows.
        $this->assertFalse($booking->isBillable());
        $this->assertSame(BookingStatus::Accepted, $booking->status);
    }

    public function test_seule_la_confirmation_du_voyageur_rend_facturable(): void
    {
        $service = app(BookingService::class);
        $listing = $this->listing();
        $booking = $service->accept($this->reserve($listing, $this->fenetre($listing), 3));

        $confirmation = StayConfirmation::create([
            'listing_id' => $listing->id,
            'traveller' => 'Rakoto',
            'nights' => 3,
            'stayed_on' => $booking->arrival,
            'points' => ['photos', 'address'],
            'flagged' => [],
            'confirmed_at' => now(),
        ]);

        $service->complete($booking->fresh(), $confirmation);

        $booking->refresh();
        $this->assertTrue($booking->isBillable());
        $this->assertSame($booking->id, $confirmation->fresh()->booking_id);
        $this->assertSame((int) round($booking->total * 0.05), $booking->commission());
    }

    public function test_on_ne_confirme_pas_une_reservation_non_acceptee(): void
    {
        $listing = $this->listing();
        $booking = $this->reserve($listing, $this->fenetre($listing), 3);

        $confirmation = StayConfirmation::create([
            'listing_id' => $listing->id, 'traveller' => 'X', 'nights' => 3,
            'stayed_on' => now()->subDays(3), 'points' => [], 'flagged' => [],
            'confirmed_at' => now(),
        ]);

        $this->expectException(BookingRefusedException::class);
        app(BookingService::class)->complete($booking, $confirmation);
    }

    public function test_une_date_passee_est_refusee(): void
    {
        $this->expectException(BookingRefusedException::class);
        $this->reserve($this->listing(), Carbon::today()->subDays(5), 3);
    }
}
