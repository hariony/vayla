<?php

namespace Tests\Feature;

use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // DatabaseSeeder plutôt que la liste des seeders : énumérer ici
        // oblige à penser aux tests chaque fois qu'un seeder s'ajoute, et
        // c'est exactement ce qui a été oublié en ajoutant les équipements.
        $this->seed();
    }

    public function test_la_page_daccueil_sert_toutes_ses_props(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Home/Index')
                ->has('destinations', 11)
                ->has('trustLevels', 4)
                ->has('categories', 8)
                ->has('listings', 8)
                ->has('photos')
                // Compté depuis la base : un nombre en dur ici se périme au
                // premier ajout de photo, et le test ne dit alors plus rien.
                ->has('credits', Photo::count())
                ->where('demo', true)
            );
    }

    /**
     * Les compteurs de l'atlas sont calculés, jamais saisis : aucun chiffre
     * affiché sur la page ne doit pouvoir mentir.
     */
    public function test_les_compteurs_de_destination_viennent_des_annonces(): void
    {
        $this->get('/')->assertInertia(fn ($page) => $page
            ->where('destinations.0.slug', 'nosy-be')
            ->where('destinations.0.listings', 2)
            ->where('destinations.0.featured', true)
        );
    }

    /**
     * Le drapeau `vayla.demo` tient deux choses ensemble : les annonces
     * fictives servies et le bandeau « Aperçu » qui le dit à l'écran.
     */
    public function test_sans_le_mode_demo_la_grille_est_vide(): void
    {
        config(['vayla.demo' => false]);

        $this->get('/')->assertInertia(fn ($page) => $page
            ->has('listings', 0)
            ->where('demo', false)
            ->where('destinations.0.listings', 0)
        );
    }
}
