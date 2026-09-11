<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Listing;
use App\Services\Office\OfficeStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Les statistiques : **des comptes définis, rangés à la bonne date**.
 *
 * Ce que ces tests tiennent : une demande se range au mois où elle a été faite,
 * un séjour au mois du départ (la règle de la facture) ; le délai est une
 * médiane ; le taux de réponse ignore les demandes encore en attente ; et la
 * démonstration se retire.
 */
class OfficeStatsTest extends TestCase
{
    use RefreshDatabase;

    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        // On part d'une table vide : les réservations de démonstration du
        // seeder brouilleraient les comptes que ces tests posent eux-mêmes.
        Booking::query()->delete();
    }

    private function connecte(): static
    {
        $admin = Admin::query()->firstOrCreate(['email' => 'equipe@vayla.test'], [
            'name' => 'Équipe', 'password' => 'une-phrase-de-passe-assez-longue', 'password_set_at' => now(),
        ]);

        return $this->actingAs($admin, 'admin');
    }

    private function demande(Carbon $faite, BookingStatus $statut, ?int $heuresAvantReponse = null, bool $demo = false, ?Carbon $depart = null): Booking
    {
        $listing = Listing::query()->firstOrFail();
        $depart ??= $faite->copy()->addDays(20);

        $b = new Booking([
            'reference' => 'VY-T'.str_pad((string) ++$this->n, 4, 'A', STR_PAD_LEFT),
            'listing_id' => $listing->id,
            'traveller' => 'Rakoto',
            'traveller_phone' => '+261341234567',
            'guests' => 2,
            'arrival' => $depart->copy()->subDays(3)->toDateString(),
            'departure' => $depart->toDateString(),
            'nights' => 3,
            'price_per_night' => 100000,
            'total' => 300000,
            'commission_rate' => 0.05,
            'status' => $statut,
            'answered_at' => $heuresAvantReponse === null ? null : $faite->copy()->addHours($heuresAvantReponse),
            'is_demo' => $demo,
        ]);
        $b->created_at = $faite;
        $b->save();

        return $b;
    }

    private function rapport(bool $avecDemo = true): array
    {
        return app(OfficeStatsService::class)->rapport(12, $avecDemo);
    }

    public function test_une_demande_se_range_au_mois_ou_elle_a_ete_faite(): void
    {
        $ilYADeuxMois = Carbon::today()->startOfMonth()->subMonthsNoOverflow(2)->addDays(3);
        $this->demande($ilYADeuxMois, BookingStatus::Accepted, 5);
        $this->demande($ilYADeuxMois, BookingStatus::Expired);

        $r = $this->rapport();
        $acceptees = collect($r['demandes'])->firstWhere('cle', 'acceptees')['valeurs'];
        $expirees = collect($r['demandes'])->firstWhere('cle', 'expirees')['valeurs'];

        $this->assertSame(1, $acceptees[9]);
        $this->assertSame(1, $expirees[9]);
        $this->assertSame(2, $r['chiffres']['demandes']);
    }

    /** Le délai est une médiane : un propriétaire lent ne fait pas croire que tous le sont. */
    public function test_le_delai_de_reponse_est_une_mediane(): void
    {
        $jour = Carbon::today()->startOfMonth()->subMonthsNoOverflow(1)->addDays(2);
        $this->demande($jour, BookingStatus::Accepted, 2);
        $this->demande($jour, BookingStatus::Declined, 4);
        $this->demande($jour, BookingStatus::Accepted, 47);

        $this->assertSame(4.0, (float) $this->rapport()['chiffres']['delaiMedian']);
    }

    /** Une demande encore en attente n'a pas encore échoué : elle ne compte pas contre le taux. */
    public function test_le_taux_de_reponse_ignore_les_demandes_en_attente(): void
    {
        $jour = Carbon::today()->startOfMonth()->subMonthsNoOverflow(1)->addDays(2);
        $this->demande($jour, BookingStatus::Accepted, 3);
        $this->demande($jour, BookingStatus::Expired);
        $this->demande($jour, BookingStatus::Pending);

        $this->assertSame(50.0, (float) $this->rapport()['chiffres']['tauxReponse']);
    }

    /** Un séjour et sa commission se rangent au mois du départ — comme la facture. */
    public function test_un_sejour_se_range_au_mois_du_depart(): void
    {
        $faite = Carbon::today()->startOfMonth()->subMonthsNoOverflow(3)->addDays(20);
        $depart = Carbon::today()->startOfMonth()->subMonthsNoOverflow(2)->addDays(5);
        $this->demande($faite, BookingStatus::Completed, 6, depart: $depart);

        $r = $this->rapport();
        $sejours = collect($r['sejours'])->firstWhere('cle', 'sejours')['valeurs'];
        $commission = collect($r['argent'])->firstWhere('cle', 'facturee')['valeurs'];

        $this->assertSame(1, $sejours[9]);
        $this->assertSame(0, $sejours[8]);
        $this->assertSame(15000, $commission[9]);
        $this->assertSame(15000, $r['chiffres']['commission']);
    }

    /** La démonstration se retire : ses réservations ne passent pas pour l'activité réelle. */
    public function test_la_demonstration_se_retire(): void
    {
        $jour = Carbon::today()->startOfMonth()->subMonthsNoOverflow(1)->addDays(2);
        $this->demande($jour, BookingStatus::Accepted, 3, demo: true);
        $this->demande($jour, BookingStatus::Accepted, 3);

        $this->assertSame(2, $this->rapport(true)['chiffres']['demandes']);
        $this->assertSame(1, $this->rapport(false)['chiffres']['demandes']);

        $this->connecte()->get('http://office.localhost/statistiques?demo=0')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('demo.inclus', false)->where('chiffres.demandes', 1));
    }

    public function test_la_periode_se_choisit_et_se_borne(): void
    {
        $this->connecte()->get('http://office.localhost/statistiques?periode=6')
            ->assertInertia(fn ($page) => $page->where('periode.mois', 6)->where('mois.courts', fn ($m) => count($m) === 6));

        $this->connecte()->get('http://office.localhost/statistiques?periode=500')
            ->assertInertia(fn ($page) => $page->where('periode.mois', 12));
    }

    /** Un mois sans demande tranchée n'est pas un taux nul : la courbe s'interrompt. */
    public function test_un_mois_sans_donnee_n_est_pas_un_zero(): void
    {
        $taux = collect($this->rapport()['reponse'])->firstWhere('cle', 'taux')['valeurs'];

        $this->assertNull($taux[0]);
    }

    public function test_l_historique_de_demonstration_est_refuse_hors_du_local(): void
    {
        $this->artisan('vayla:historique-demo')->assertFailed();
    }
}
