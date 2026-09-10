<?php

namespace Tests\Feature;

use App\Enums\BlockReason;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Owner;
use App\Models\Unavailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Le calendrier du propriétaire.
 *
 * Deux familles de tests, et les deux protègent une chose différente :
 *
 * 1. **La règle de la nuit.** Une période va de l'arrivée à la veille du
 *    départ. Se tromper d'un jour retire une nuit vendable à chaque période
 *    du calendrier — c'est l'erreur qui fait mentir le prix affiché, et elle
 *    ne se voit pas à l'œil.
 * 2. **La portée de la clé.** Elle ouvre l'espace d'un propriétaire, pas
 *    celui du voisin : ni le calendrier d'un autre logement, ni la
 *    réouverture d'une période qui ne lui appartient pas.
 */
class OwnerCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function hanta(): Owner
    {
        return Owner::query()->where('name', 'like', 'Hanta%')->firstOrFail();
    }

    private function villa(): Listing
    {
        return Listing::query()->where('slug', 'villa-ambatoloaka')->firstOrFail();
    }

    private function url(string $suffixe = ''): string
    {
        return '/proprietaire/logements/villa-ambatoloaka/calendrier'.$suffixe;
    }

    private function hantaConnectee(): static
    {
        return $this->actingAs($this->hanta(), 'proprietaire');
    }

    /** Une fenêtre libre, loin des périodes semées par la démonstration. */
    private function libre(int $decalage = 300): array
    {
        $arrivee = Carbon::today()->addDays($decalage);

        return [$arrivee->toDateString(), $arrivee->copy()->addDays(4)->toDateString()];
    }

    public function test_le_calendrier_s_ouvre_avec_la_cle(): void
    {
        $this->hantaConnectee()->get($this->url())
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Owner/Calendar')
                ->where('listing.slug', 'villa-ambatoloaka')
                ->has('declared', 4)
                ->has('reasons', 5)
                // Les bornes de séjour du logement ne s'appliquent pas au
                // propriétaire : il doit pouvoir fermer une seule soirée.
                ->where('calendar.minNights', 1)
                ->where('calendar.maxNights', null));
    }

    public function test_un_visiteur_non_connecte_n_ouvre_aucun_calendrier(): void
    {
        $this->get($this->url())->assertRedirect('/proprietaire/connexion');
    }

    /**
     * **Le test qui compte pour la donnée.** Le propriétaire pose une arrivée
     * et un jour de libération ; on stocke la veille du départ. Un séjour du
     * 12 au 16 ferme quatre nuits — 12, 13, 14, 15 — et le 16 reste vendable.
     */
    public function test_la_derniere_nuit_est_la_veille_du_depart(): void
    {
        [$arrivee, $depart] = $this->libre();

        $this->hantaConnectee()->post($this->url(), [
            'arrival' => $arrivee,
            'departure' => $depart,
            'reason' => 'loue_direct',
        ])->assertRedirect()->assertSessionHas('succes');

        $periode = Unavailability::query()
            ->where('listing_id', $this->villa()->id)
            ->whereDate('starts_on', $arrivee)
            ->firstOrFail();

        $this->assertSame(
            Carbon::parse($depart)->subDay()->toDateString(),
            $periode->ends_on->toDateString(),
            'Le jour du départ doit rester réservable.'
        );
        $this->assertSame(BlockReason::LoueDirect, $periode->reason);
    }

    /** Le calendrier public reprend aussitôt la période : c'est tout l'objet de l'écran. */
    public function test_la_periode_ferme_les_nuits_sur_la_fiche_publique(): void
    {
        [$arrivee, $depart] = $this->libre();

        $this->hantaConnectee()->post($this->url(), [
            'arrival' => $arrivee,
            'departure' => $depart,
            'reason' => 'occupe',
        ])->assertRedirect();

        $this->get('/logements/villa-ambatoloaka')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                'fiche.calendar.blocked',
                fn ($periodes) => collect($periodes)->contains(
                    fn ($p) => $p['from'] === $arrivee
                        && $p['to'] === Carbon::parse($depart)->subDay()->toDateString()
                )
            ));
    }

    public function test_on_ne_ferme_pas_une_date_passee(): void
    {
        $this->hantaConnectee()->post($this->url(), [
            'arrival' => Carbon::yesterday()->toDateString(),
            'departure' => Carbon::today()->addDays(3)->toDateString(),
            'reason' => 'occupe',
        ])->assertSessionHasErrors('arrival');
    }

    public function test_le_depart_vient_apres_l_arrivee(): void
    {
        $jour = Carbon::today()->addDays(300)->toDateString();

        $this->hantaConnectee()->post($this->url(), [
            'arrival' => $jour,
            'departure' => $jour,
            'reason' => 'occupe',
        ])->assertSessionHasErrors('departure');
    }

    /**
     * Au-delà de l'horizon du calendrier, la période serait invisible dans la
     * grille : elle fermerait des nuits que personne ne pourrait rouvrir.
     */
    public function test_on_ne_ferme_pas_au_dela_de_l_horizon(): void
    {
        $this->hantaConnectee()->post($this->url(), [
            'arrival' => Carbon::today()->addMonths(11)->toDateString(),
            'departure' => Carbon::today()->addMonths(14)->toDateString(),
            'reason' => 'occupe',
        ])->assertSessionHasErrors('departure');
    }

    public function test_le_motif_appartient_au_vocabulaire(): void
    {
        [$arrivee, $depart] = $this->libre();

        $this->hantaConnectee()->post($this->url(), [
            'arrival' => $arrivee,
            'departure' => $depart,
            'reason' => 'parce que',
        ])->assertSessionHasErrors('reason');
    }

    /**
     * Deux périodes déclarées ne se fusionnent pas en silence : le
     * propriétaire croirait avoir ajouté une période et en retrouverait une
     * autre. Le refus nomme celle qui gêne.
     */
    public function test_deux_periodes_declarees_ne_se_chevauchent_pas(): void
    {
        [$arrivee, $depart] = $this->libre();

        $this->hantaConnectee()->post($this->url(), ['arrival' => $arrivee, 'departure' => $depart, 'reason' => 'occupe'])
            ->assertSessionHas('succes');

        $this->hantaConnectee()->post($this->url(), [
            'arrival' => Carbon::parse($arrivee)->addDay()->toDateString(),
            'departure' => Carbon::parse($depart)->addDays(3)->toDateString(),
            'reason' => 'occupe',
        ])->assertSessionHas('erreur');

        $this->assertSame(1, Unavailability::query()
            ->where('listing_id', $this->villa()->id)
            ->whereDate('starts_on', '>=', $arrivee)
            ->count());
    }

    /**
     * **La borne qui coûte cher.** Arriver le jour où une période se libère
     * doit passer : la nuit du départ n'est pas occupée. Un décalage d'un
     * jour ici ferme une nuit vendable de plus à chaque période.
     */
    public function test_on_ferme_a_partir_du_jour_ou_la_periode_precedente_se_libere(): void
    {
        [$arrivee, $depart] = $this->libre();

        $this->hantaConnectee()->post($this->url(), ['arrival' => $arrivee, 'departure' => $depart, 'reason' => 'occupe'])
            ->assertSessionHas('succes');

        // La période précédente occupe jusqu'à la veille de `$depart` : le
        // jour de `$depart` est donc libre, et doit se fermer sans conflit.
        $this->hantaConnectee()->post($this->url(), [
            'arrival' => $depart,
            'departure' => Carbon::parse($depart)->addDays(2)->toDateString(),
            'reason' => 'occupe',
        ])->assertSessionHas('succes')->assertSessionMissing('erreur');
    }

    /**
     * Fermer des nuits déjà vendues ferait disparaître un séjour sans que
     * personne ne l'apprenne. Le refus nomme la réservation à traiter.
     */
    public function test_une_reservation_en_cours_interdit_la_fermeture(): void
    {
        [$arrivee, $depart] = $this->libre();

        Booking::create([
            'reference' => 'VY-CAL01',
            'listing_id' => $this->villa()->id,
            'traveller' => 'Rakoto Jean',
            'traveller_phone' => '+261340000099',
            'guests' => 2,
            'arrival' => $arrivee,
            'departure' => $depart,
            'nights' => 4,
            'price_per_night' => 100000,
            'total' => 400000,
            'commission_rate' => 0.05,
            'status' => BookingStatus::Accepted,
        ]);

        $reponse = $this->hantaConnectee()->post($this->url(), [
            'arrival' => $arrivee,
            'departure' => $depart,
            'reason' => 'occupe',
        ]);

        $reponse->assertSessionHas('erreur');
        $this->assertStringContainsString('VY-CAL01', session('erreur'));
    }

    public function test_rouvrir_une_periode(): void
    {
        $periode = Unavailability::query()->where('listing_id', $this->villa()->id)->firstOrFail();

        $this->hantaConnectee()->post($this->url('/'.$periode->id.'/liberer'))
            ->assertRedirect()
            ->assertSessionHas('succes');

        $this->assertDatabaseMissing('unavailabilities', ['id' => $periode->id]);
    }

    /**
     * **Le test qui compte pour la sécurité.** Une clé valide plus un
     * identifiant deviné ne doivent pas rouvrir le calendrier d'un confrère.
     */
    public function test_on_ne_rouvre_pas_la_periode_d_un_autre_logement(): void
    {
        // `front-de-mer-amborovy` appartient à Voahangy, pas à Hanta.
        $autre = Listing::query()->where('slug', 'front-de-mer-amborovy')->firstOrFail();
        $periode = Unavailability::query()->where('listing_id', $autre->id)->firstOrFail();

        // Même en passant par le calendrier d'un logement qu'elle possède.
        $this->hantaConnectee()->post($this->url('/'.$periode->id.'/liberer'))
            ->assertRedirect()
            ->assertSessionHas('erreur');

        $this->assertDatabaseHas('unavailabilities', ['id' => $periode->id]);
    }

    public function test_on_n_ouvre_pas_le_calendrier_du_logement_d_un_autre(): void
    {
        $this->hantaConnectee()
            ->get('/proprietaire/logements/front-de-mer-amborovy/calendrier')
            ->assertNotFound();
    }
}
