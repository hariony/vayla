<?php

namespace Tests\Feature;

use App\Enums\AdminActionKind;
use App\Enums\NotificationKind;
use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\OutboundMessage;
use App\Models\Owner;
use App\Models\Page;
use App\Services\Support\LaunchMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * **La collecte des logements, avant l'ouverture.**
 *
 * Tant que `vayla.lancement` est actif, le site n'a qu'un public : les
 * propriétaires. Le test tient les deux bouts, parce qu'aucun ne suffit seul :
 *
 * 1. **Ce qui est fermé l'est côté serveur**, pas seulement retiré des menus :
 *    une adresse partagée, un favori ou un catalogue indexé la veille ne
 *    doivent rien rouvrir.
 * 2. **Ce qui reste ouvert marche vraiment** : la page d'arrivée, la porte, la
 *    fiche du logement et les informations — sinon la publicité mène à une
 *    impasse.
 *
 * S'y ajoutent les deux outils de la collecte : la source de chaque
 * inscription, et l'inscription d'un propriétaire par l'équipe.
 */
class LaunchModeTest extends TestCase
{
    use RefreshDatabase;

    private const OFFICE = 'http://office.localhost';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Http::fake();
    }

    private function lancer(): void
    {
        config(['vayla.lancement' => true]);
    }

    private function proprietaire(): Owner
    {
        return Owner::query()->where('is_demo', true)->firstOrFail();
    }

    // ── Hors collecte, rien ne change ────────────────────────────────

    public function test_hors_collecte_le_site_reste_ouvert(): void
    {
        $this->get('/')->assertOk();
        $this->get('/logements')->assertOk();
        $this->get('/louer-mon-logement')->assertOk()->assertInertia(fn ($p) => $p->component('Owners/Landing')->where('lancement', false));
    }

    // ── Pendant la collecte ──────────────────────────────────────────

    public function test_les_pages_publiques_renvoient_vers_la_page_des_proprietaires(): void
    {
        $this->lancer();

        foreach (['/', '/logements', '/destinations', '/demande', '/connexion', '/connexion/client', '/inscription', '/comment-ca-marche'] as $adresse) {
            $this->get($adresse)->assertRedirect('/louer-mon-logement');
        }
    }

    public function test_l_ancienne_adresse_redirige_en_gardant_la_source(): void
    {
        $this->get('/proprietaires?source=facebook-nosybe')
            ->assertStatus(301)
            ->assertRedirect('/louer-mon-logement?source=facebook-nosybe');

        $this->lancer();
        $this->get('/proprietaires')->assertStatus(301)->assertRedirect('/louer-mon-logement');
    }

    public function test_un_geste_ferme_repond_404_plutot_que_de_rediriger(): void
    {
        $this->lancer();

        // Un POST renvoyé vers une page perdrait ce qu'il portait sans rien dire.
        $this->post('/demande', ['name' => 'Claire', 'email' => 'claire@example.com'])->assertNotFound();
    }

    public function test_l_api_est_fermee(): void
    {
        $this->lancer();

        $this->getJson('/api/v1/listings')->assertStatus(503);
    }

    public function test_la_porte_des_proprietaires_reste_ouverte_et_hors_index(): void
    {
        $this->lancer();

        $this->get('/louer-mon-logement')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertInertia(fn ($p) => $p->component('Owners/Landing')->where('lancement', true));

        $this->get('/proprietaire/inscription')->assertOk();
        $this->get('/proprietaire/connexion')->assertOk();
    }

    public function test_seules_les_pages_legales_publiees_restent_ouvertes(): void
    {
        $this->lancer();

        Page::query()->where('slug', 'mentions-legales')->update(['is_published' => true, 'published_at' => now()]);

        $this->get('/mentions-legales')->assertOk();
        $this->get('/comment-ca-marche')->assertRedirect('/louer-mon-logement');
    }

    public function test_l_espace_proprietaire_est_reduit_a_sa_fiche_et_a_ses_informations(): void
    {
        $this->lancer();
        $owner = $this->proprietaire();
        $slug = $owner->listings()->firstOrFail()->slug;

        // L'adresse où tout propriétaire connecté atterrit mène à ses logements.
        $this->actingAs($owner, 'proprietaire')->get('/proprietaire')->assertRedirect('/proprietaire/logements');

        foreach (['/proprietaire/logements', '/proprietaire/logements/nouveau', "/proprietaire/logements/{$slug}/modifier", '/proprietaire/compte'] as $adresse) {
            $this->actingAs($owner, 'proprietaire')->get($adresse)->assertOk();
        }

        foreach (['/proprietaire/messages', '/proprietaire/reservations', '/proprietaire/facturation', "/proprietaire/logements/{$slug}/calendrier"] as $adresse) {
            $this->actingAs($owner, 'proprietaire')->get($adresse)->assertRedirect('/louer-mon-logement');
        }
    }

    /**
     * **Les rubriques marquées `lancement` dans `espaces.js` sont exactement
     * celles que le serveur laisse ouvertes.** Une rubrique affichée mais
     * fermée serait un lien mort ; une rubrique ouverte mais cachée, un écran
     * qu'on ne trouve pas.
     */
    public function test_les_rubriques_du_lancement_sont_celles_qui_restent_ouvertes(): void
    {
        $espaces = file_get_contents(resource_path('js/Support/espaces.js'));
        preg_match_all("/\{[^{}]*href: '([^']+)'[^{}]*lancement: true[^{}]*\}/", $espaces, $rubriques);

        $this->assertSame(['/proprietaire/logements', '/proprietaire/compte'], $rubriques[1]);

        $this->lancer();
        $owner = $this->proprietaire();

        foreach ($rubriques[1] as $href) {
            $this->actingAs($owner, 'proprietaire')->get($href)->assertOk();
        }
    }

    public function test_le_back_office_n_est_jamais_touche(): void
    {
        $this->lancer();

        $this->get(self::OFFICE.'/connexion')->assertOk();
    }

    public function test_une_route_ajoutee_plus_tard_est_fermee_par_defaut(): void
    {
        // La liste est écrite par nom : rien ne s'y ajoute par accident.
        $this->assertNotContains('home', LaunchMode::ROUTES_OUVERTES);
        $this->assertNotContains('owner.home', LaunchMode::ROUTES_OUVERTES);
        $this->assertNotContains('stay-requests.store', LaunchMode::ROUTES_OUVERTES);
    }

    // ── La source d'une inscription ──────────────────────────────────

    public function test_la_source_du_lien_est_ecrite_a_la_naissance_du_compte(): void
    {
        $this->lancer();

        $this->withSession(['inscription.proprietaire.verifiee' => 'nouvelle-proprio@example.com'])
            ->get('/louer-mon-logement?source=Facebook-NosyBe')
            ->assertOk();

        $this->post('/proprietaire/inscription/fiche', ['name' => 'Hanta Rasoa', 'phone' => '034 12 345 67'])
            ->assertRedirect('/proprietaire/logements/nouveau');

        $this->assertSame('facebook-nosybe', Owner::query()->where('email', 'nouvelle-proprio@example.com')->value('source'));
    }

    public function test_une_source_malformee_est_oubliee_sans_fermer_la_porte(): void
    {
        $this->withSession(['inscription.proprietaire.verifiee' => 'nouvelle-proprio@example.com'])
            ->get('/proprietaire/inscription?source=%3Cscript%3E')
            ->assertOk();

        $this->post('/proprietaire/inscription/fiche', ['name' => 'Hanta Rasoa', 'phone' => '034 12 345 67']);

        $this->assertNull(Owner::query()->where('email', 'nouvelle-proprio@example.com')->value('source'));
    }

    // ── L'équipe inscrit un propriétaire ─────────────────────────────

    private function equipe(): Admin
    {
        return Admin::query()->firstOrCreate(['email' => 'equipe@vayla.test'], [
            'name' => 'Voahirana Andria',
            'password' => 'une-phrase-de-passe-assez-longue',
            'password_set_at' => now(),
        ]);
    }

    public function test_l_equipe_inscrit_un_proprietaire_et_prepare_son_lien(): void
    {
        $this->actingAs($this->equipe(), 'admin')
            ->post(self::OFFICE.'/proprietaires', ['name' => 'Rivo Rakoto', 'phone' => '034 98 765 43', 'email' => '', 'city' => 'Nosy Be'])
            ->assertRedirect();

        $owner = Owner::query()->where('phone', '+261349876543')->firstOrFail();

        $this->assertSame('equipe', $owner->source);
        $this->assertNull($owner->email);
        $this->assertNotNull($owner->access_key);
        // Créer n'est pas vérifier.
        $this->assertFalse($owner->telephoneVerifie());

        $this->assertTrue(OutboundMessage::query()->where('owner_id', $owner->id)->where('kind', NotificationKind::LienAcces->value)->exists());
        $this->assertTrue(AdminAction::query()->where('kind', AdminActionKind::OwnerCreated->value)->exists());
    }

    public function test_un_numero_deja_inscrit_n_ouvre_pas_un_second_compte(): void
    {
        $existant = $this->proprietaire();

        $this->actingAs($this->equipe(), 'admin')
            ->from(self::OFFICE.'/proprietaires')
            ->post(self::OFFICE.'/proprietaires', ['name' => 'Doublon', 'phone' => $existant->phone])
            ->assertSessionHasErrors('phone');

        $this->assertSame(1, Owner::query()->where('phone', $existant->phone)->count());
    }
}
