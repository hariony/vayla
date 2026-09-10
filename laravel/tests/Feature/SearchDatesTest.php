<?php

namespace Tests\Feature;

use App\Enums\BlockReason;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Unavailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * La recherche par dates — le site et l'API, mêmes bornes, même règle.
 *
 * Le moteur de la page d'accueil portait deux champs de dates depuis le
 * début : ils étaient collectés puis **jetés**. Ni le filtre en mémoire, ni la
 * requête partagée, ni le dépôt ne les connaissaient. Deux champs qui ne
 * filtrent rien en silence sont pires que pas de champs du tout.
 *
 * Ce que ces tests protègent surtout, c'est **la nuit du départ**. Une nuit
 * appartient à sa date d'arrivée : un séjour du 12 au 15 occupe 12, 13, 14 et
 * libère le 15. Se tromper d'un jour dans un sens masque des logements
 * disponibles, dans l'autre en propose d'indisponibles — et le voyageur ne
 * s'en aperçoit qu'au refus du propriétaire.
 */
class SearchDatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Unavailability::query()->delete();
        Booking::query()->delete();
    }

    private function listing(): Listing
    {
        return Listing::query()->where('slug', 'villa-ambatoloaka')->firstOrFail();
    }

    private function jour(int $dans): string
    {
        return Carbon::today()->addDays($dans)->toDateString();
    }

    /** @return array<int, string> Les slugs rendus par la recherche. */
    private function chercher(string $arrivee, string $depart): array
    {
        return collect(
            $this->getJson("/api/v1/listings?arrival={$arrivee}&departure={$depart}&per_page=50")
                ->assertOk()
                ->json('data')
        )->pluck('slug')->all();
    }

    public function test_une_periode_declaree_retire_le_logement(): void
    {
        Unavailability::create([
            'listing_id' => $this->listing()->id,
            'starts_on' => $this->jour(10),
            'ends_on' => $this->jour(14),
            'reason' => BlockReason::LoueDirect,
        ]);

        $this->assertNotContains('villa-ambatoloaka', $this->chercher($this->jour(12), $this->jour(16)));
    }

    /**
     * La borne haute : `ends_on` est la **dernière nuit occupée**. Une
     * recherche qui commence le lendemain doit passer.
     */
    public function test_on_peut_arriver_le_lendemain_de_la_derniere_nuit_occupee(): void
    {
        Unavailability::create([
            'listing_id' => $this->listing()->id,
            'starts_on' => $this->jour(10),
            'ends_on' => $this->jour(14),
            'reason' => BlockReason::LoueDirect,
        ]);

        $this->assertContains('villa-ambatoloaka', $this->chercher($this->jour(15), $this->jour(18)));
    }

    /**
     * La borne basse, et c'est celle qui coûte cher : un séjour qui **part**
     * le jour où la période commence ne la chevauche pas. Le comparer avec
     * `>=` retirerait une nuit vendable à chaque recherche.
     */
    public function test_partir_le_jour_ou_la_periode_commence_reste_possible(): void
    {
        Unavailability::create([
            'listing_id' => $this->listing()->id,
            'starts_on' => $this->jour(20),
            'ends_on' => $this->jour(24),
            'reason' => BlockReason::LoueDirect,
        ]);

        $this->assertContains('villa-ambatoloaka', $this->chercher($this->jour(17), $this->jour(20)));
    }

    public function test_une_reservation_bloquante_retire_le_logement(): void
    {
        $this->reservation(BookingStatus::Accepted, null);

        $this->assertNotContains('villa-ambatoloaka', $this->chercher($this->jour(31), $this->jour(34)));
    }

    /**
     * Une demande dont le délai a coulé mais que la commande horaire n'a pas
     * encore vue ne doit pas continuer à masquer le logement : la recherche
     * appliquerait un blocage que le calendrier de la fiche a déjà rendu.
     */
    public function test_une_demande_expiree_ne_bloque_plus(): void
    {
        $this->reservation(BookingStatus::Pending, Carbon::now()->subHour());

        $this->assertContains('villa-ambatoloaka', $this->chercher($this->jour(31), $this->jour(34)));
    }

    public function test_une_demande_refusee_ne_bloque_pas(): void
    {
        $this->reservation(BookingStatus::Declined, null);

        $this->assertContains('villa-ambatoloaka', $this->chercher($this->jour(31), $this->jour(34)));
    }

    public function test_un_sejour_plus_court_que_le_minimum_du_logement_est_ecarte(): void
    {
        $this->listing()->update(['min_nights' => 5]);

        $this->assertNotContains('villa-ambatoloaka', $this->chercher($this->jour(40), $this->jour(42)));
        $this->assertContains('villa-ambatoloaka', $this->chercher($this->jour(40), $this->jour(45)));
    }

    // ── Les bornes du contrat ────────────────────────────────────────

    public function test_une_arrivee_sans_depart_est_refusee(): void
    {
        $this->getJson('/api/v1/listings?arrival='.$this->jour(5))
            ->assertStatus(422)
            ->assertJsonValidationErrors('departure');
    }

    public function test_un_depart_avant_l_arrivee_est_refuse(): void
    {
        $this->getJson('/api/v1/listings?arrival='.$this->jour(9).'&departure='.$this->jour(5))
            ->assertStatus(422)
            ->assertJsonValidationErrors('departure');
    }

    public function test_une_arrivee_dans_le_passe_est_refusee(): void
    {
        $this->getJson('/api/v1/listings?arrival='.$this->jour(-3).'&departure='.$this->jour(2))
            ->assertStatus(422)
            ->assertJsonValidationErrors('arrival');
    }

    public function test_un_sejour_de_plus_d_un_an_est_refuse(): void
    {
        $this->getJson('/api/v1/listings?arrival='.$this->jour(1).'&departure='.$this->jour(400))
            ->assertStatus(422)
            ->assertJsonValidationErrors('departure');
    }

    /**
     * Sur le site, un couple de dates invalide rouvre le catalogue propre —
     * jamais un 422 en pleine figure, et jamais une éjection vers l'accueil.
     */
    public function test_le_site_rouvre_le_catalogue_sur_des_dates_invalides(): void
    {
        $this->get('/logements?arrival='.$this->jour(9).'&departure='.$this->jour(5))
            ->assertRedirect('/logements');
    }

    public function test_sans_dates_la_recherche_ne_filtre_rien(): void
    {
        $this->getJson('/api/v1/listings?per_page=50')
            ->assertOk()
            ->assertJsonPath('meta.total', 8);
    }

    // ── Le report du séjour sur la fiche ─────────────────────────────

    /**
     * Un voyageur qui a posé ses dates dans le moteur ne doit pas les
     * ressaisir sur la fiche : c'est le moment exact où l'on abandonne.
     */
    public function test_la_fiche_reprend_le_sejour_venu_de_la_recherche(): void
    {
        $this->get('/logements/villa-ambatoloaka?arrival='.$this->jour(40).'&departure='.$this->jour(44))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('sejour.arrival', $this->jour(40))
                ->where('sejour.departure', $this->jour(44))
                ->where('sejour.nights', 4));
    }

    /**
     * Le séjour est **une suggestion, pas un critère** : une paire absurde
     * dans l'URL ne pré-remplit rien, et surtout ne casse pas la page. Un
     * 422 ici renverrait une erreur pour un simple confort d'affichage.
     */
    public function test_un_sejour_absurde_dans_l_url_n_empeche_pas_d_ouvrir_la_fiche(): void
    {
        $this->get('/logements/villa-ambatoloaka?arrival=n-importe-quoi&departure=2026-13-45')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('sejour', null));
    }

    public function test_sans_dates_la_fiche_ne_propose_aucun_sejour(): void
    {
        $this->get('/logements/villa-ambatoloaka')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('sejour', null));
    }

    private function reservation(BookingStatus $statut, ?Carbon $expire): void
    {
        Booking::create([
            'reference' => 'VY-TEST1',
            'listing_id' => $this->listing()->id,
            'traveller' => 'Test',
            'traveller_phone' => '+261340000000',
            'guests' => 2,
            'arrival' => $this->jour(30),
            'departure' => $this->jour(35),
            'nights' => 5,
            'price_per_night' => 100000,
            'total' => 500000,
            'commission_rate' => 0.05,
            'status' => $statut,
            'hold_expires_at' => $expire,
        ]);
    }
}
