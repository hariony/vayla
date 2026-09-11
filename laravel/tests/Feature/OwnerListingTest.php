<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Http\Requests\OwnerListingRequest;
use App\Models\Amenity;
use App\Models\Destination;
use App\Models\Listing;
use App\Models\Owner;
use App\Services\OwnerListingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La saisie d'une annonce par son propriétaire.
 *
 * **Le test central n'est pas que le formulaire enregistre — c'est qu'il ne
 * publie pas.** Un propriétaire remplit sa fiche, la soumet, et Vayla la
 * contrôle avant qu'elle n'apparaisse. Sans ce palier, n'importe qui se
 * mettrait en ligne au niveau qu'il veut, et « vérifié » ne voudrait plus
 * rien dire : c'est le seul actif du produit.
 */
class OwnerListingTest extends TestCase
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

    private function connectee(): static
    {
        return $this->actingAs($this->hanta(), 'proprietaire');
    }

    /** @return array<string, mixed> */
    private function fiche(array $ecrase = []): array
    {
        return array_merge([
            'title' => 'Maison de la pointe, Andilana',
            'destination_id' => Destination::query()->value('id'),
            'kind' => 'maison',
            'summary' => 'Deux chambres à cent mètres de la plage.',
            'description' => str_repeat('Une vraie description du logement, de ses pièces et de son accès. ', 3),
            'guests' => 4,
            'bedrooms' => 2,
            'beds' => 3,
            'bathrooms' => 1,
            'price' => 120000,
            'min_nights' => 2,
            'check_in_from' => '14:00',
            'check_out_before' => '11:00',
        ], $ecrase);
    }

    public function test_le_proprietaire_cree_une_annonce_en_brouillon(): void
    {
        $this->connectee()->post('/proprietaire/logements', $this->fiche())->assertRedirect();

        $annonce = Listing::query()->where('title', 'Maison de la pointe, Andilana')->firstOrFail();

        $this->assertSame(ListingStatus::Draft, $annonce->status);
        $this->assertSame($this->hanta()->id, $annonce->owner_id);
        $this->assertSame('maison-de-la-pointe-andilana', $annonce->slug);
    }

    /**
     * **Le test qui protège tout le produit.** Le niveau de confiance
     * n'entre par aucun formulaire : il est attribué par Vayla après
     * vérification. Une annonce neuve part de « déclarée », quoi qu'on poste.
     */
    public function test_le_niveau_de_confiance_ne_se_declare_pas(): void
    {
        $this->connectee()
            ->post('/proprietaire/logements', $this->fiche([
                'trust_level' => 4,
                'status' => 'published',
                'featured' => true,
            ]))
            ->assertRedirect();

        $annonce = Listing::query()->where('title', 'Maison de la pointe, Andilana')->firstOrFail();

        $this->assertSame(1, $annonce->trust_level->value);
        $this->assertSame(ListingStatus::Draft, $annonce->status);
        $this->assertFalse($annonce->featured);
    }

    /** Une annonce en brouillon n'apparaît nulle part côté voyageur. */
    public function test_un_brouillon_n_est_pas_visible_du_public(): void
    {
        $this->connectee()->post('/proprietaire/logements', $this->fiche());

        $this->get('/logements/maison-de-la-pointe-andilana')->assertNotFound();

        $this->get('/logements')
            ->assertInertia(fn ($page) => $page->where('listings', fn ($l) => ! collect($l)
                ->contains(fn ($a) => $a['slug'] === 'maison-de-la-pointe-andilana')));
    }

    /**
     * On refuse une fiche incomplète **en disant ce qui manque** : Vayla
     * appellerait sinon pour réclamer, et le propriétaire aurait
     * l'impression d'avoir travaillé pour rien.
     */
    public function test_une_fiche_incomplete_ne_part_pas_en_verification(): void
    {
        $this->connectee()->post('/proprietaire/logements', $this->fiche());
        $annonce = Listing::query()->where('slug', 'maison-de-la-pointe-andilana')->firstOrFail();

        $this->connectee()
            ->post("/proprietaire/logements/{$annonce->slug}/soumettre")
            ->assertSessionHas('erreur');

        $this->assertStringContainsString('photos', session('erreur'));
        $this->assertSame(ListingStatus::Draft, $annonce->fresh()->status);
    }

    public function test_une_fiche_complete_part_en_verification(): void
    {
        $annonce = Listing::query()->where('slug', 'villa-ambatoloaka')->firstOrFail();
        $annonce->update(['status' => ListingStatus::Draft]);

        $this->connectee()
            ->post("/proprietaire/logements/{$annonce->slug}/soumettre")
            ->assertSessionHas('succes');

        // **Soumise, pas publiée.** C'est Vayla qui met en ligne.
        $this->assertSame(ListingStatus::Submitted, $annonce->fresh()->status);
        $this->get('/logements/villa-ambatoloaka')->assertNotFound();
    }

    public function test_les_equipements_coches_se_retrouvent_sur_l_annonce(): void
    {
        $ids = Amenity::query()->limit(4)->pluck('id')->all();

        $this->connectee()->post('/proprietaire/logements', $this->fiche([
            'amenities' => [
                ['id' => $ids[0], 'highlight' => true],
                ['id' => $ids[1], 'highlight' => false],
                ['id' => $ids[2], 'highlight' => true],
            ],
        ]))->assertRedirect();

        $annonce = Listing::query()->where('slug', 'maison-de-la-pointe-andilana')->firstOrFail();

        $this->assertCount(3, $annonce->amenities);
        $this->assertSame(2, $annonce->amenities->where('pivot.highlight', true)->count());
    }

    /**
     * **Une annonce vérifiée se referme partiellement.** Le tarif et la
     * description bougent ; la capacité, elle, ne bouge plus — sans quoi la
     * vérification porterait sur un logement qui n'existe plus.
     */
    public function test_une_annonce_verifiee_ne_change_plus_de_capacite(): void
    {
        $annonce = Listing::query()->where('slug', 'villa-ambatoloaka')->firstOrFail();
        $this->assertSame(ListingStatus::Published, $annonce->status);

        $capacite = $annonce->guests;

        $this->connectee()->post("/proprietaire/logements/{$annonce->slug}", $this->fiche([
            'title' => $annonce->title,
            'destination_id' => $annonce->destination_id,
            'guests' => 30,
            'price' => 199000,
        ]))->assertRedirect();

        $frais = $annonce->fresh();

        $this->assertSame($capacite, $frais->guests, 'La capacité ne doit plus bouger après vérification.');
        $this->assertSame(199000, $frais->price, 'Le tarif, lui, doit rester libre.');
    }

    /** Le slug est l'adresse publique : il ne bouge pas quand le titre change. */
    public function test_le_slug_ne_bouge_pas_quand_le_titre_change(): void
    {
        $this->connectee()->post('/proprietaire/logements', $this->fiche());
        $annonce = Listing::query()->where('slug', 'maison-de-la-pointe-andilana')->firstOrFail();

        $this->connectee()->post("/proprietaire/logements/{$annonce->slug}",
            $this->fiche(['title' => 'Un tout autre nom']));

        $this->assertSame('maison-de-la-pointe-andilana', $annonce->fresh()->slug);
        $this->assertSame('Un tout autre nom', $annonce->fresh()->title);
    }

    /**
     * **Le test qui compte pour la sécurité.** Un slug se lit dans le
     * catalogue public : il ne doit pas ouvrir la fiche d'un confrère.
     */
    public function test_on_ne_modifie_pas_l_annonce_d_un_autre(): void
    {
        // `front-de-mer-amborovy` appartient à Voahangy, pas à Hanta.
        $this->connectee()->get('/proprietaire/logements/front-de-mer-amborovy/modifier')->assertNotFound();
        $this->connectee()->post('/proprietaire/logements/front-de-mer-amborovy', $this->fiche())->assertNotFound();
        $this->connectee()->post('/proprietaire/logements/front-de-mer-amborovy/soumettre')->assertNotFound();
    }

    public function test_un_visiteur_non_connecte_ne_saisit_rien(): void
    {
        $this->get('/proprietaire/logements')->assertRedirect('/proprietaire/connexion');
        $this->get('/proprietaire/logements/nouveau')->assertRedirect('/proprietaire/connexion');
        $this->post('/proprietaire/logements', $this->fiche())->assertRedirect('/proprietaire/connexion');
    }

    public function test_le_formulaire_recoit_les_cent_deux_equipements_groupes(): void
    {
        $this->connectee()->get('/proprietaire/logements/nouveau')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Owner/Listings/Form')
                ->where('listing', null)
                ->has('destinations')
                ->has('kinds')
                // Groupés par rubrique : cent deux cases en une liste sont
                // illisibles, et le propriétaire abandonne avant la moitié.
                ->where('amenityGroups', fn ($g) => count($g) > 5
                    && collect($g)->sum(fn ($r) => count($r['amenities'])) === Amenity::count()));
    }

    /** Les bornes ne sont pas décoratives : une annonce à 999 999 999 Ar fait douter de tout le reste. */
    public function test_les_valeurs_aberrantes_sont_refusees(): void
    {
        $this->connectee()->post('/proprietaire/logements', $this->fiche(['price' => 999999999]))
            ->assertSessionHasErrors('price');

        $this->connectee()->post('/proprietaire/logements', $this->fiche(['guests' => 400]))
            ->assertSessionHasErrors('guests');

        $this->connectee()->post('/proprietaire/logements', $this->fiche(['min_nights' => 10, 'max_nights' => 2]))
            ->assertSessionHasErrors('max_nights');
    }

    public function test_le_service_liste_toutes_les_rubriques_non_vides(): void
    {
        $groupes = app(OwnerListingService::class)->vocabulaire()['amenityGroups'];

        foreach ($groupes as $g) {
            $this->assertNotEmpty($g['amenities'], "Rubrique vide publiée : {$g['key']}");
            $this->assertNotEmpty($g['label']);
        }
    }

    /**
     * **La création dépose sur l'étape des photos.** C'est la prochaine chose
     * à faire, et la seule que la création ne pouvait pas faire : les photos
     * s'attachent à un logement qui doit déjà exister.
     */
    public function test_la_creation_depose_sur_l_etape_des_photos(): void
    {
        $this->connectee()
            ->post('/proprietaire/logements', $this->fiche())
            ->assertRedirect('/proprietaire/logements/maison-de-la-pointe-andilana/modifier?etape=photos');
    }

    /**
     * **Chaque règle du formulaire appartient à une étape.** C'est ce qui
     * ramène une erreur du serveur à l'étape où elle se lit : un champ que
     * les étapes ne déclarent pas serait une erreur reçue sur la mauvaise
     * étape — donc invisible, et un bouton « Créer » qui refuse sans dire
     * pourquoi.
     */
    public function test_chaque_regle_du_formulaire_appartient_a_une_etape(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Owner/Listings/Form.vue'));

        preg_match('/const ETAPES = \[(.*?)\n\]/s', $source, $bloc);
        $this->assertNotEmpty($bloc, 'Le formulaire doit déclarer ses étapes.');

        preg_match_all("/'([a-z_]+)'/", $bloc[1], $mots);
        $champs = $mots[1];

        $regles = array_keys((new OwnerListingRequest)->rules());

        foreach ($regles as $regle) {
            // `amenities.*.id` appartient à l'étape qui porte `amenities`.
            $racine = explode('.', $regle)[0];

            $this->assertContains($racine, $champs, "La règle « {$regle} » n'appartient à aucune étape du formulaire.");
        }
    }
}
