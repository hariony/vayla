<?php

namespace Tests\Feature;

use App\Enums\AdminActionKind;
use App\Enums\StayRequestStatus;
use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\Destination;
use App\Models\StayRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La demande de séjour « dans l'autre sens », et les liens de l'accueil qui y
 * mènent.
 *
 * Les quatre boutons « Déposer une demande » pointaient sur `#demande` —
 * la section même où se trouvait le premier : il n'y avait rien derrière.
 * Ces tests tiennent la promesse de l'accueil de bout en bout : la demande
 * se dépose sans compte, arrive dans la file du back-office, et s'y traite.
 */
class StayRequestTest extends TestCase
{
    use RefreshDatabase;

    private const HOTE = 'http://office.localhost';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function demande(array $ecrase = []): array
    {
        return array_merge([
            'destination' => Destination::query()->value('slug'),
            'guests' => 4,
            'budget' => '150 000',
            'name' => 'Claire Martin',
            'phone' => '034 12 345 67',
            'message' => 'Au calme, avec un groupe électrogène.',
        ], $ecrase);
    }

    private function equipe(): static
    {
        $admin = Admin::query()->firstOrCreate(['email' => 'equipe@vayla.test'], [
            'name' => 'Voahirana Andria', 'password' => 'une-phrase-de-passe-assez-longue', 'password_set_at' => now(),
        ]);

        return $this->actingAs($admin, 'admin');
    }

    // ── Le formulaire ───────────────────────────────────────────────────

    /** La recherche de l'accueil arrive avec le clic. */
    public function test_le_formulaire_reprend_la_recherche_en_cours(): void
    {
        $slug = Destination::query()->value('slug');

        $this->get("/demande?destination={$slug}&guests=4&arrival=2027-01-10&departure=2027-01-15")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Demande/Create')
                ->where('initial.destination', $slug)
                ->where('initial.guests', 4)
                ->where('initial.arrival', '2027-01-10')
                ->where('envoyee', null));
    }

    /** Sans compte : un nom et un numéro suffisent, et le numéro est normalisé. */
    public function test_une_demande_se_depose_sans_compte(): void
    {
        $this->post('/demande', $this->demande())
            ->assertRedirect('/demande')
            ->assertSessionHas('demandeEnvoyee');

        $demande = StayRequest::query()->sole();
        $this->assertSame(['+261341234567', 150000, 4, StayRequestStatus::New], [$demande->phone, $demande->budget, $demande->guests, $demande->status]);
        $this->assertNotNull($demande->destination_id);

        // Après l'envoi, le récapitulatif — plus de formulaire.
        $this->get('/demande')->assertInertia(fn ($page) => $page->where('envoyee.canal', 'whatsapp')->where('envoyee.nom', 'Claire Martin'));
    }

    public function test_sans_moyen_de_repondre_la_demande_est_refusee(): void
    {
        $this->post('/demande', $this->demande(['phone' => '', 'email' => '']))
            ->assertSessionHasErrors(['phone', 'email']);

        $this->assertSame(0, StayRequest::query()->count());
    }

    /** Les dates vont par paire, comme dans le moteur de recherche. */
    public function test_une_arrivee_sans_depart_est_refusee(): void
    {
        $this->post('/demande', $this->demande(['arrival' => now()->addMonth()->toDateString()]))
            ->assertSessionHasErrors('departure');
    }

    /** Le champ piège : un robot qui remplit tout le remplit aussi. */
    public function test_le_champ_piege_arrete_les_robots(): void
    {
        $this->post('/demande', $this->demande(['site' => 'https://spam.example']))->assertSessionHasErrors('site');
        $this->assertSame(0, StayRequest::query()->count());
    }

    // ── La file du back-office ──────────────────────────────────────────

    public function test_la_demande_arrive_dans_la_file_et_s_y_traite(): void
    {
        $this->post('/demande', $this->demande());
        $demande = StayRequest::query()->sole();

        $this->equipe()->get(self::HOTE.'/demandes')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Office/Demandes/Index')
                ->where('demandes.0.nom', 'Claire Martin')
                ->where('demandes.0.whatsapp', fn ($lien) => str_starts_with($lien, 'https://wa.me/261341234567'))
                ->where('demandes.0.catalogue', fn ($lien) => str_contains($lien, '/logements?destination='))
                ->where('officeCompteurs.demandes', 1));

        $this->equipe()->post(self::HOTE."/demandes/{$demande->id}/prendre")->assertSessionHas('succes');
        $this->assertSame(StayRequestStatus::Taken, $demande->fresh()->status);
        $this->assertNotNull($demande->fresh()->admin_id);

        // Clore demande une note.
        $this->equipe()->post(self::HOTE."/demandes/{$demande->id}/clore", ['note' => ''])->assertSessionHasErrors('note');
        $this->equipe()->post(self::HOTE."/demandes/{$demande->id}/clore", ['note' => 'Trois logements proposés à Ambatoloaka.'])->assertSessionHas('succes');

        $this->assertSame(StayRequestStatus::Closed, $demande->fresh()->status);
        $this->assertSame(
            [AdminActionKind::StayRequestTaken, AdminActionKind::StayRequestClosed],
            AdminAction::query()->orderBy('id')->pluck('kind')->all(),
        );
    }

    // ── Les liens qui y mènent ──────────────────────────────────────────

    /**
     * **Plus aucun bouton ne pointe sur `#demande`** : c'était la section où se
     * trouvait le premier d'entre eux. Et les repères de la carte, qui se
     * donnent pour des boutons, font quelque chose au clic et au clavier.
     */
    public function test_les_liens_de_demande_menent_au_formulaire(): void
    {
        foreach ([
            'js/Pages/Home/Partials/AskSection.vue',
            'js/Pages/Home/Partials/OffersSection.vue',
            'js/Pages/Home/Partials/AtlasSection.vue',
            'js/Components/SiteFooter.vue',
            'js/Pages/Destinations/Show.vue',
        ] as $fichier) {
            $source = file_get_contents(resource_path($fichier));
            $this->assertStringNotContainsString('href="#demande"', $source, $fichier);
            $this->assertStringNotContainsString('href="/#demande"', $source, $fichier);
            $this->assertStringNotContainsString("hash: 'demande'", $source, $fichier);
        }

        $carte = file_get_contents(resource_path('js/Components/MadagascarMap.vue'));
        $this->assertStringContainsString('role="button"', $carte);
        $this->assertStringContainsString("@click=\"\$emit('pick', d.slug)\"", $carte);
        $this->assertStringContainsString('@keydown.enter', $carte);

        $this->get('/demande')->assertOk();
    }
}
