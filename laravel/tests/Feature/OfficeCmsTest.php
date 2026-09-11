<?php

namespace Tests\Feature;

use App\Enums\AdminActionKind;
use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\Page;
use App\Models\SiteText;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les textes du site, tenus depuis le back-office : les pages éditoriales
 * entières et les textes de l'accueil.
 *
 * Ce que ces tests tiennent :
 *
 * - **le HTML tapé ne passe pas** — ni balise, ni lien `javascript:` : un
 *   compte d'équipe compromis ne doit pas pouvoir poser un script sur le site ;
 * - **une page ne prend pas l'adresse d'un écran**, et son adresse se fige à la
 *   publication ;
 * - **une page « à compléter » ne se publie pas** — les pages légales
 *   naissent ainsi ;
 * - **le pied de page ne porte plus de lien mort** : seules les pages publiées
 *   y apparaissent ;
 * - **chaque texte a sa borne**, et l'original reste à un geste.
 */
class OfficeCmsTest extends TestCase
{
    use RefreshDatabase;

    private const HOTE = 'http://office.localhost';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function connecte(): static
    {
        $admin = Admin::query()->firstOrCreate(['email' => 'equipe@vayla.test'], [
            'name' => 'Équipe', 'password' => 'une-phrase-de-passe-assez-longue', 'password_set_at' => now(),
        ]);

        return $this->actingAs($admin, 'admin');
    }

    private function office(string $chemin): string
    {
        return self::HOTE.$chemin;
    }

    // ── Les pages sur le site ───────────────────────────────────────────

    public function test_une_page_publiee_s_ouvre_a_son_adresse_courte(): void
    {
        $this->get('/comment-ca-marche')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Content/Show')
                ->where('page.titre', 'Comment ça marche')
                ->where('page.sommaire', fn ($s) => count($s) >= 3));
    }

    public function test_un_brouillon_et_une_adresse_inconnue_repondent_404(): void
    {
        $this->assertFalse(Page::query()->where('slug', 'mentions-legales')->value('is_published'));

        $this->get('/mentions-legales')->assertNotFound();
        $this->get('/une-page-qui-n-existe-pas')->assertNotFound();
    }

    /** La route des pages est la dernière : aucun écran du site ne tombe dessus. */
    public function test_les_ecrans_du_site_passent_avant_les_pages(): void
    {
        $this->get('/logements')->assertOk()->assertInertia(fn ($p) => $p->component('Listings/Index'));
        $this->get('/destinations')->assertOk()->assertInertia(fn ($p) => $p->component('Destinations/Index'));
        $this->get('/connexion')->assertOk()->assertInertia(fn ($p) => $p->component('Access/Index'));
    }

    /** **Le HTML tapé ne passe pas** : ni balise, ni lien `javascript:`. */
    public function test_le_html_et_les_liens_dangereux_sont_retires(): void
    {
        Page::query()->where('slug', 'a-propos')->update([
            'body' => "## Titre\n\n<script>alert('x')</script>\n\n<img src=x onerror=alert(1)>\n\n[piège](javascript:alert(1)) et [ailleurs](https://example.org)",
        ]);

        $html = $this->get('/a-propos')->assertOk()->viewData('page')['props']['page']['html'];

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    /** Le taux n'est jamais recopié dans une page : il vient du réglage. */
    public function test_la_commission_s_ecrit_depuis_le_reglage(): void
    {
        app(SettingsService::class)->ecrire(SettingsService::COMMISSION, '0.07');

        $html = $this->get('/tarifs')->assertOk()->viewData('page')['props']['page']['html'];

        $this->assertStringContainsString("7\u{00A0}%", $html);
        $this->assertStringNotContainsString('{commission}', $html);
    }

    // ── Le pied de page ─────────────────────────────────────────────────

    /** Seules les pages publiées ont un lien : le pied de page portait cinq liens `#`. */
    public function test_le_pied_de_page_ne_porte_que_des_pages_publiees(): void
    {
        $this->get('/')->assertInertia(fn ($page) => $page->where('pied', function ($pied) {
            $liens = collect($pied)->flatten(1)->pluck('href');

            return $liens->contains('/comment-ca-marche')
                && $liens->contains('/tarifs')
                && ! $liens->contains('/mentions-legales');
        }));

        $pied = file_get_contents(resource_path('js/Components/SiteFooter.vue'));
        preg_match('/<script setup>(.*)<\/script>/s', $pied, $script);
        $this->assertStringNotContainsString("'#'", $script[1], 'Plus aucun lien vers « # » dans le pied de page.');
    }

    // ── Les pages au back-office ────────────────────────────────────────

    public function test_une_page_s_ecrit_se_publie_et_se_retire(): void
    {
        $this->connecte()->post($this->office('/pages'), [
            'title' => 'Saisons et climat', 'body' => "## Quand venir\n\nD'avril à novembre.", 'footer_group' => 'voyageurs',
        ])->assertRedirect();

        $page = Page::query()->where('slug', 'saisons-et-climat')->firstOrFail();
        $this->get('/saisons-et-climat')->assertNotFound();

        $this->connecte()->post($this->office("/pages/{$page->id}/publier"))->assertSessionHas('succes');
        $this->get('/saisons-et-climat')->assertOk();

        $this->connecte()->post($this->office("/pages/{$page->id}/depublier"))->assertSessionHas('succes');
        $this->get('/saisons-et-climat')->assertNotFound();

        $this->assertSame(
            [AdminActionKind::PageSaved, AdminActionKind::PagePublished, AdminActionKind::PageUnpublished],
            AdminAction::query()->orderBy('id')->pluck('kind')->all(),
        );
    }

    /** Une page ne prend pas l'adresse d'un écran du site. */
    public function test_une_page_ne_prend_pas_l_adresse_d_un_ecran(): void
    {
        foreach (['logements', 'connexion', 'proprietaire', 'api'] as $adresse) {
            $this->connecte()->post($this->office('/pages'), ['title' => 'Piège', 'slug' => $adresse])->assertSessionHas('erreur');
        }

        $this->assertFalse(Page::query()->where('title', 'Piège')->exists());
    }

    /** Publiée une fois, son adresse ne bouge plus : elle a pu être partagée. */
    public function test_l_adresse_se_fige_a_la_publication(): void
    {
        $page = Page::query()->where('slug', 'a-propos')->firstOrFail();

        $this->connecte()->post($this->office("/pages/{$page->id}"), ['title' => 'Qui sommes-nous', 'slug' => 'qui-sommes-nous', 'body' => $page->body]);

        $this->assertSame(['a-propos', 'Qui sommes-nous'], [$page->fresh()->slug, $page->fresh()->title]);
    }

    /** Les pages légales naissent « à compléter », et ne se publient pas tant qu'elles le sont. */
    public function test_une_page_a_completer_ne_se_publie_pas(): void
    {
        $legal = Page::query()->where('slug', 'mentions-legales')->firstOrFail();

        $this->connecte()->post($this->office("/pages/{$legal->id}/publier"))->assertSessionHas('erreur');
        $this->assertFalse($legal->fresh()->is_published);
    }

    public function test_une_page_attendue_par_le_site_ne_se_supprime_pas(): void
    {
        $page = Page::query()->where('slug', 'conditions-d-utilisation')->firstOrFail();

        $this->connecte()->post($this->office("/pages/{$page->id}/supprimer"))->assertSessionHas('erreur');
        $this->assertNotNull($page->fresh());
    }

    public function test_l_apercu_est_rendu_par_le_serveur(): void
    {
        $this->connecte()->postJson($this->office('/pages/apercu'), ['body' => "## Un titre\n\n**gras** <b>brut</b>"])
            ->assertOk()
            ->assertJsonPath('sommaire.0.label', 'Un titre')
            ->assertJson(fn ($json) => $json->where('html', fn ($h) => str_contains($h, '<strong>gras</strong>') && ! str_contains($h, '<b>'))->etc());
    }

    // ── Les textes du site ──────────────────────────────────────────────

    /** Réécrit au back-office, le texte est sur l'accueil aussitôt. */
    public function test_un_texte_de_l_accueil_se_reecrit_et_se_retablit(): void
    {
        $this->connecte()->post($this->office('/textes'), [
            'groupe' => 'accueil-ouverture',
            'textes' => ['accueil.hero.titre' => 'Des maisons vérifiées, partout.'],
        ])->assertSessionHas('succes');

        // Les clés portent des points : on lit le tableau, pas un chemin.
        $this->get('/')->assertInertia(fn ($page) => $page->where('textes', fn ($t) => $t['accueil.hero.titre'] === 'Des maisons vérifiées, partout.'));
        $this->assertTrue(AdminAction::query()->where('kind', AdminActionKind::SiteTextsChanged->value)->exists());

        // Rétablir l'original, c'est effacer la réécriture.
        $this->connecte()->post($this->office('/textes'), [
            'groupe' => 'accueil-ouverture',
            'textes' => ['accueil.hero.titre' => 'Location de villas et appartements meublés.'],
        ]);

        $this->assertSame(0, SiteText::query()->count());
        $this->get('/')->assertInertia(fn ($page) => $page->where('textes', fn ($t) => $t['accueil.hero.titre'] === 'Location de villas et appartements meublés.'));
    }

    /**
     * **Chaque texte a sa borne.** La ligne soulignée déborde d'un téléphone
     * au-delà de vingt caractères — et les points de sa clé ne doivent pas
     * faire sauter la règle.
     */
    public function test_la_ligne_soulignee_est_bornee_a_vingt_caracteres(): void
    {
        $this->connecte()->post($this->office('/textes'), [
            'groupe' => 'accueil-ouverture',
            'textes' => ['accueil.hero.titre_souligne' => str_repeat('a', 21)],
        ])->assertSessionHasErrors();

        $this->assertSame(0, SiteText::query()->count());
    }

    /** Ce que l'équipe tape est affiché, jamais interprété : le composable échappe d'abord. */
    public function test_les_textes_sont_echappes_avant_d_etre_affiches(): void
    {
        $source = file_get_contents(resource_path('js/Composables/useTextes.js'));

        $this->assertMatchesRegularExpression('/enrichir = \(s\) => echapper\(s\)/', $source);
    }

    public function test_chaque_ecran_de_contenu_repond(): void
    {
        $page = Page::query()->firstOrFail();

        foreach (['/textes', '/pages', '/pages/nouvelle', "/pages/{$page->id}"] as $chemin) {
            $this->connecte()->get($this->office($chemin))->assertOk();
        }
    }
}
