<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CataloguePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_le_catalogue_sert_ses_props(): void
    {
        $this->get('/logements')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Listings/Index')
                ->has('listings', 8)
                ->has('amenityFilters')
                ->has('sorts', 4)
                ->has('trustLevels', 4)
                ->where('meta.total', 8)
                ->where('facets.catalogue', 8)
                ->where('demo', true)
            );
    }

    public function test_les_facettes_ne_proposent_que_ce_qui_existe(): void
    {
        $page = $this->get('/logements')->assertOk()->viewData('page');
        $facets = $page['props']['facets'];

        // Proposer « Chambre chez l'habitant » sans aucune chambre produirait
        // un filtre qui ne ramène rien, et un catalogue qui a l'air cassé.
        $kinds = array_column($facets['kinds'], 'key');
        $this->assertContains('villa', $kinds);
        $this->assertNotContains('chambre', $kinds);

        $this->assertSame(70000, $facets['priceMin']);
        $this->assertSame(185000, $facets['priceMax']);
    }

    public function test_les_criteres_de_lurl_filtrent_et_reviennent_au_front(): void
    {
        $this->get('/logements?amenities[]=groupe-electrogene&amenities[]=piscine-privee')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings', 1)
                ->where('listings.0.slug', 'villa-ambatoloaka')
                // Le serveur renvoie les critères retenus : sans cet écho, le
                // panneau mentirait après un retour arrière.
                ->where('filtre.amenities', ['groupe-electrogene', 'piscine-privee'])
            );
    }

    public function test_le_tri_par_prix_est_croissant(): void
    {
        $page = $this->get('/logements?sort=prix-asc')->assertOk()->viewData('page');
        $prix = array_column($page['props']['listings'], 'price');

        $trie = $prix;
        sort($trie);

        $this->assertSame($trie, $prix);
    }

    public function test_un_critere_invalide_ramene_au_catalogue_pas_a_laccueil(): void
    {
        // Un lien partagé qui porte un filtre périmé doit rouvrir le
        // catalogue, pas éjecter vers la page d'accueil.
        $this->get('/logements?kind=chateau')->assertRedirect('/logements');
        $this->get('/logements?per_page=100')->assertRedirect('/logements');
    }

    public function test_la_fiche_sert_la_description_et_les_equipements_groupes(): void
    {
        $this->get('/logements/case-ifaty')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Listings/Show')
                ->where('fiche.listing.slug', 'case-ifaty')
                ->where('fiche.listing.kindLabel', 'Bungalow')
                ->has('fiche.description')
                ->has('fiche.amenities')
                ->has('trustLevels', 4)
            );
    }

    public function test_une_annonce_inconnue_repond_404_en_html(): void
    {
        // « Introuvable » est un fait métier, pas une panne : une URL
        // d'annonce périmée ne doit pas renvoyer un 500 et être indexée
        // comme telle.
        $this->get('/logements/nexiste-pas')->assertNotFound();
    }

    public function test_les_logements_voisins_excluent_lannonce_courante(): void
    {
        $page = $this->get('/logements/villa-ambatoloaka')->assertOk()->viewData('page');
        $voisins = array_column($page['props']['similar'], 'slug');

        $this->assertNotContains('villa-ambatoloaka', $voisins);
        $this->assertContains('bungalow-madirokely', $voisins);
    }

    public function test_hors_mode_demo_la_fiche_dune_annonce_fictive_repond_404(): void
    {
        // Sans ce garde-fou, une URL directe servirait encore une annonce
        // fictive alors que le catalogue s'est vidé.
        config(['vayla.demo' => false]);

        $this->get('/logements')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('listings', 0));

        $this->get('/logements/villa-ambatoloaka')->assertNotFound();
    }
}
