<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Owner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Le tableau de bord du propriétaire connecté.
 *
 * L'espace s'ouvre par un compte — numéro et mot de passe. Ce que ces tests
 * protègent n'est plus l'accès lui-même (`OwnerAuthTest` s'en charge) mais
 * **ce qu'une session autorise** :
 *
 * 1. **Un visiteur non connecté ne voit rien** et part sur la connexion.
 * 2. **Être connecté dit qui l'on est, pas ce qu'on a le droit de toucher.**
 *    C'est le vrai risque : les références de réservation sont courtes et se
 *    dictent au téléphone. Sans contrôle d'appartenance, un propriétaire
 *    pourrait accepter la demande d'un confrère avec sa propre session.
 */
class OwnerSpaceTest extends TestCase
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

    private function demande(string $slug, string $reference = 'VY-TEST1'): Booking
    {
        $listing = Listing::query()->where('slug', $slug)->firstOrFail();

        return Booking::create([
            'reference' => $reference,
            'listing_id' => $listing->id,
            'traveller' => 'Rakoto Jean',
            'traveller_phone' => '+261340000099',
            'guests' => 2,
            'arrival' => Carbon::today()->addDays(200)->toDateString(),
            'departure' => Carbon::today()->addDays(204)->toDateString(),
            'nights' => 4,
            'price_per_night' => 100000,
            'total' => 400000,
            'commission_rate' => 0.05,
            'status' => BookingStatus::Pending,
            'hold_expires_at' => Carbon::now()->addHours(40),
        ]);
    }

    public function test_la_cle_ouvre_le_tableau_de_bord(): void
    {
        $this->demande('villa-ambatoloaka');

        $this->actingAs($this->hanta(), 'proprietaire')
            ->get('/proprietaire')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Owner/Index')
                ->where('owner.name', $this->hanta()->name)
                ->has('listings', 2)
                // On cherche **sa** demande dans la liste plutôt qu'un index :
                // le jeu de démonstration en pose d'autres, et les demandes
                // sont triées par délai restant.
                ->where('pending', fn ($demandes) => collect($demandes)->contains(
                    // La commission est visible **avant** la réponse : la
                    // découvrir sur la facture, c'est se sentir piégé.
                    fn ($d) => $d['reference'] === 'VY-TEST1' && $d['commission'] === 20000
                )));
    }

    public function test_un_visiteur_non_connecte_part_sur_la_connexion(): void
    {
        $this->get('/proprietaire')->assertRedirect('/proprietaire/connexion');
        $this->get('/proprietaire/logements/villa-ambatoloaka/calendrier')
            ->assertRedirect('/proprietaire/connexion');
    }

    /**
     * Une demande dont le délai a coulé n'est plus répondable : proposer
     * « Accepter » sur des nuits déjà rendues au calendrier ferait accepter un
     * séjour que Vayla ne peut plus garantir.
     */
    public function test_une_demande_expiree_ne_figure_plus_dans_les_demandes(): void
    {
        $this->demande('villa-ambatoloaka')->update(['hold_expires_at' => Carbon::now()->subHour()]);

        $this->actingAs($this->hanta(), 'proprietaire')
            ->get('/proprietaire')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('pending', fn ($demandes) => ! collect($demandes)
                ->contains(fn ($d) => $d['reference'] === 'VY-TEST1')));
    }

    public function test_accepter_une_demande(): void
    {
        $booking = $this->demande('villa-ambatoloaka');

        $this->actingAs($this->hanta(), 'proprietaire')
            ->post('/proprietaire/demandes/VY-TEST1/accepter')
            ->assertRedirect()
            ->assertSessionHas('succes');

        $this->assertSame(BookingStatus::Accepted, $booking->fresh()->status);
        // Le délai tombe : une demande acceptée ne doit plus expirer toute seule.
        $this->assertNull($booking->fresh()->hold_expires_at);
    }

    public function test_refuser_une_demande_transmet_le_motif(): void
    {
        $booking = $this->demande('villa-ambatoloaka');

        $this->actingAs($this->hanta(), 'proprietaire')
            ->post('/proprietaire/demandes/VY-TEST1/refuser', [
                'reason' => 'Le logement est déjà pris ces dates-là',
            ])->assertRedirect()->assertSessionHas('succes');

        $this->assertSame(BookingStatus::Declined, $booking->fresh()->status);
        $this->assertSame('Le logement est déjà pris ces dates-là', $booking->fresh()->closed_reason);
    }

    /**
     * **Le test qui compte.** Les références sont courtes et se dictent au
     * téléphone : une session valide plus une référence devinée ne doivent
     * pas suffire à répondre pour un autre propriétaire.
     */
    public function test_on_ne_repond_pas_a_la_demande_d_un_autre_proprietaire(): void
    {
        // `front-de-mer-amborovy` appartient à Voahangy, pas à Hanta.
        $booking = $this->demande('front-de-mer-amborovy', 'VY-AUTRE');

        $this->actingAs($this->hanta(), 'proprietaire')
            ->post('/proprietaire/demandes/VY-AUTRE/accepter')
            ->assertNotFound();

        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);
    }

    public function test_une_demande_deja_close_ne_se_repond_pas_deux_fois(): void
    {
        $booking = $this->demande('villa-ambatoloaka');
        $booking->update(['status' => BookingStatus::Declined]);

        $this->actingAs($this->hanta(), 'proprietaire')
            ->post('/proprietaire/demandes/VY-TEST1/accepter')
            ->assertRedirect()
            ->assertSessionHas('erreur');

        $this->assertSame(BookingStatus::Declined, $booking->fresh()->status);
    }

    /**
     * La clé et le mot de passe ouvrent des portes : ni l'une ni l'autre n'ont
     * à figurer dans une charge utile qu'un écran renverrait.
     */
    public function test_les_secrets_ne_sont_jamais_serialises(): void
    {
        $tableau = $this->hanta()->toArray();

        $this->assertArrayNotHasKey('access_key', $tableau);
        $this->assertArrayNotHasKey('password', $tableau);
        $this->assertArrayNotHasKey('remember_token', $tableau);
    }

    /** Chaque propriétaire de démonstration a une clé, sinon son espace est inatteignable. */
    public function test_chaque_proprietaire_a_une_cle(): void
    {
        foreach (Owner::all() as $owner) {
            $this->assertNotEmpty($owner->access_key, "Propriétaire sans clé : {$owner->name}");
            $this->assertGreaterThanOrEqual(32, strlen($owner->access_key));
        }
    }
}
