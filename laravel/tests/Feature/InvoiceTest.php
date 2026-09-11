<?php

namespace Tests\Feature;

use App\Data\DateRangeData;
use App\DTOs\Bookings\NewBookingDto;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Owner;
use App\Models\StayConfirmation;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * La facture est le seul document que Vayla envoie à un propriétaire.
 * Ce qui est verrouillé ici, c'est ce qui la rend **défendable** : elle ne
 * contient que des séjours qu'il peut vérifier lui-même.
 */
class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function sejourTermine(string $slug, Carbon $arrivee, int $nuits = 3): Booking
    {
        $service = app(BookingService::class);
        $listing = Listing::with(['unavailabilities', 'bookings'])->where('slug', $slug)->firstOrFail();

        $booking = $service->book($listing, new NewBookingDto(
            traveller: 'Rakoto',
            travellerPhone: '+261 34 12 345 67',
            travellerEmail: null,
            guests: 2,
            arrival: $arrivee->toDateString(),
            departure: $arrivee->copy()->addDays($nuits)->toDateString(),
        ));

        $service->accept($booking);

        // Le séjour est passé : on recule les dates comme le ferait le temps.
        $booking->update([
            'arrival' => Carbon::today()->subDays(12),
            'departure' => Carbon::today()->subDays(12 - $nuits),
        ]);

        $confirmation = StayConfirmation::create([
            'listing_id' => $listing->id,
            'traveller' => 'Rakoto',
            'nights' => $nuits,
            'stayed_on' => Carbon::today()->subDays(12),
            'points' => ['photos', 'address'],
            'flagged' => [],
            'confirmed_at' => Carbon::today()->subDays(12 - $nuits - 1),
        ]);

        $service->complete($booking->fresh(), $confirmation);

        return $booking->fresh();
    }

    private function fenetre(Listing $listing, int $nuits = 3): Carbon
    {
        $prises = collect(app(AvailabilityService::class)->blocked($listing));

        for ($d = 1; $d < 300; $d++) {
            $debut = Carbon::today()->addDays($d);
            $libre = true;
            for ($i = 0; $i < $nuits; $i++) {
                $jour = $debut->copy()->addDays($i)->toDateString();
                if ($prises->contains(fn (DateRangeData $p) => $jour >= $p->from && $jour <= $p->to)) {
                    $libre = false;
                    break;
                }
            }
            if ($libre) {
                return $debut;
            }
        }

        $this->fail('Aucune fenêtre libre.');
    }

    public function test_la_facture_ne_contient_que_des_sejours_confirmes(): void
    {
        $listing = Listing::with(['unavailabilities', 'bookings'])->where('slug', 'villa-ambatoloaka')->firstOrFail();
        $termine = $this->sejourTermine('villa-ambatoloaka', $this->fenetre($listing), 3);

        // Une réservation seulement acceptée, jamais confirmée : elle ne doit
        // pas produire de ligne. Facturer l'acceptation, c'est facturer les
        // no-shows — le propriétaire refuserait de payer.
        $service = app(BookingService::class);
        $frais = Listing::with(['unavailabilities', 'bookings'])->where('slug', 'villa-ambatoloaka')->firstOrFail();
        $service->accept($service->book($frais, new NewBookingDto(
            traveller: 'Jamais venu', travellerPhone: '+261 34 00 00 00', travellerEmail: null, guests: 2,
            arrival: $this->fenetre($frais, 3)->toDateString(),
            departure: $this->fenetre($frais, 3)->addDays(3)->toDateString(),
        )));

        $facture = app(InvoiceService::class)->forOwner($termine->listing->owner, $termine->departure)->toArray();

        $this->assertSame(1, $facture['stays']);
        $this->assertSame($termine->reference, $facture['lines'][0]['reference']);
        $this->assertStringContainsString('confirmés', $facture['basis']);
    }

    /**
     * **Le séjour qui finit le dernier jour du mois est sur cette facture.**
     *
     * Il n'y était pas : `whereBetween` comparait `2026-08-31 00:00:00` — ce
     * que SQLite range dans une colonne `date` — à la borne `2026-08-31`, et
     * la chaîne la plus longue l'emportait. Le séjour tombait entre deux
     * factures, sans apparaître sur aucune. Les autres tests ne le voyaient
     * que les jours où leur date calculée tombait sur un 31 : une suite qui
     * rougit selon le calendrier ne prouve rien. Celui-ci fixe la date.
     */
    public function test_un_sejour_termine_le_dernier_jour_du_mois_est_facture(): void
    {
        $listing = Listing::with(['unavailabilities', 'bookings'])->where('slug', 'villa-ambatoloaka')->firstOrFail();
        $booking = $this->sejourTermine('villa-ambatoloaka', $this->fenetre($listing), 3);

        $finDeMois = Carbon::create(2026, 1, 31)->startOfDay();
        $booking->update([
            'arrival' => $finDeMois->copy()->subDays(3),
            'departure' => $finDeMois,
        ]);

        $facture = app(InvoiceService::class)->forOwner($booking->listing->owner, $finDeMois)->toArray();

        $this->assertSame(1, $facture['stays'], 'Le séjour du 31 doit figurer sur la facture de janvier.');
        $this->assertSame('2026-01-31', $facture['lines'][0]['departure']);

        // Et il n'est pas compté deux fois, sur le mois suivant.
        $suivant = app(InvoiceService::class)->forOwner($booking->listing->owner, $finDeMois->copy()->addMonthNoOverflow())->toArray();
        $this->assertSame(0, $suivant['stays']);
    }

    public function test_la_commission_vient_des_valeurs_figees(): void
    {
        $listing = Listing::with(['unavailabilities', 'bookings'])->where('slug', 'maison-itasy')->firstOrFail();
        $booking = $this->sejourTermine('maison-itasy', $this->fenetre($listing), 3);

        $attendu = (int) round($booking->total * 0.05);

        // Le tarif de l'annonce triple et le taux double : la facture déjà
        // engagée ne bouge pas. Une facture qui change après coup est une
        // facture qu'on ne paie pas.
        $booking->listing->update(['price' => $booking->listing->price * 3]);
        config(['vayla.commission.rate' => 0.10]);

        $facture = app(InvoiceService::class)->forOwner($booking->listing->owner, $booking->departure)->toArray();

        $this->assertSame($attendu, $facture['due']);
        $this->assertSame(0.05, $facture['lines'][0]['rate']);
    }

    public function test_un_proprietaire_sans_sejour_ne_recoit_pas_de_facture(): void
    {
        // Envoyer une facture à zéro use la relation pour rien.
        $factures = app(InvoiceService::class)->forMonth(Carbon::today());

        $this->assertEmpty($factures);
        $this->assertSame(0, app(InvoiceService::class)
            ->forOwner(Owner::first(), Carbon::today())->toArray()['stays']);
    }

    public function test_le_mois_retenu_est_celui_de_la_fin_du_sejour(): void
    {
        $listing = Listing::with(['unavailabilities', 'bookings'])->where('slug', 'lodge-andasibe')->firstOrFail();
        $booking = $this->sejourTermine('lodge-andasibe', $this->fenetre($listing), 3);

        $service = app(InvoiceService::class);

        // Facturé au mois du départ, pas à celui de la réservation.
        $this->assertSame(1, $service->forOwner($booking->listing->owner, $booking->departure)->toArray()['stays']);
        $this->assertSame(0, $service->forOwner(
            $booking->listing->owner,
            $booking->departure->copy()->addMonthNoOverflow()
        )->toArray()['stays']);
    }

    public function test_la_facture_porte_de_quoi_etre_reglee(): void
    {
        $listing = Listing::with(['unavailabilities', 'bookings'])->where('slug', 'villa-ambatoloaka')->firstOrFail();
        $booking = $this->sejourTermine('villa-ambatoloaka', $this->fenetre($listing), 3);

        $facture = app(InvoiceService::class)->forOwner($booking->listing->owner, $booking->departure)->toArray();

        // Le propriétaire pousse l'argent : il lui faut son numéro et son
        // opérateur. Vayla ne stocke rien qui permettrait de le débiter.
        $this->assertNotEmpty($facture['owner']['mobileMoney']);
        $this->assertNotEmpty($facture['owner']['operator']);

        // Et de quoi vérifier chaque ligne sans nous croire sur parole.
        $ligne = $facture['lines'][0];
        foreach (['reference', 'listing', 'traveller', 'arrival', 'departure', 'nights', 'total', 'commission'] as $cle) {
            $this->assertArrayHasKey($cle, $ligne);
        }
    }

    /**
     * **Le mois en cours s'affiche avant les factures, et n'en est pas une.**
     * Le cacher jusqu'au premier du mois suivant ferait découvrir un montant
     * qu'on aurait pu voir venir — et c'est exactement ce qui fait qu'une
     * commission se sent comme un piège.
     */
    public function test_l_ecran_de_facturation_montre_le_mois_en_cours_et_l_historique(): void
    {
        $listing = Listing::with(['unavailabilities', 'bookings'])->where('slug', 'villa-ambatoloaka')->firstOrFail();
        $owner = $listing->owner;

        $this->actingAs($owner, 'proprietaire')
            ->get('/proprietaire/facturation')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Owner/Invoices')
                ->has('facturation.encours')
                ->has('facturation.factures')
                ->where('taux', (float) config('vayla.commission.rate'))
            );
    }

    /**
     * **Un mois sans séjour confirmé n'entre pas dans l'historique.** Une
     * ligne à zéro n'apprend rien et allonge une liste qu'on parcourt pour
     * retrouver un montant.
     */
    public function test_l_historique_ecarte_les_mois_sans_sejour(): void
    {
        $listing = Listing::with(['unavailabilities', 'bookings'])->where('slug', 'villa-ambatoloaka')->firstOrFail();
        $booking = $this->sejourTermine('villa-ambatoloaka', $this->fenetre($listing), 3);

        $historique = app(InvoiceService::class)->historique($booking->listing->owner)->toArray();

        // Un séjour terminé il y a douze jours tombe forcément dans la
        // fenêtre : soit dans le mois en cours, soit dans la facture qui
        // précède. La somme le retrouve sans dépendre du jour où le test
        // tourne — ce qui a déjà coûté une suite rouge un 31.
        $vus = $historique['encours']['stays'] + collect($historique['factures'])->sum('stays');
        $this->assertGreaterThan(0, $vus);

        foreach ($historique['factures'] as $facture) {
            $this->assertGreaterThan(0, $facture['stays'], 'Un mois vide n’a rien à faire dans l’historique.');
        }
    }

    public function test_la_facturation_est_fermee_aux_visiteurs(): void
    {
        $this->get('/proprietaire/facturation')->assertRedirect('/proprietaire/connexion');
    }
}
