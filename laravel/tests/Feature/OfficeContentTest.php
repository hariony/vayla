<?php

namespace Tests\Feature;

use App\Enums\AdminActionKind;
use App\Enums\BookingStatus;
use App\Enums\ListingStatus;
use App\Enums\TrustLevel;
use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Listing;
use App\Models\Owner;
use App\Models\Photo;
use App\Services\BookingService;
use App\Services\Office\OfficeContentReadService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DestinationSeeder;
use Database\Seeders\PhotoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * La gestion de contenu du back-office : le texte et les photos des annonces,
 * les destinations, les catégories, les équipements, les réglages.
 *
 * Ce que ces tests tiennent, au-delà du « ça enregistre » :
 *
 * - **une clé publique ne bouge jamais** — slug, clé de catégorie ou
 *   d'équipement : un lien partagé qui casse est un voyageur perdu ;
 * - **on ne supprime pas ce qui porte une déclaration** ;
 * - **ce qui se déduit ne se saisit pas** — ni « Séjour confirmé », ni le
 *   niveau de confiance, ni le statut ;
 * - **chaque geste laisse une ligne au journal** ;
 * - **un `make seed` n'écrase plus ce que l'équipe a modifié**.
 */
class OfficeContentTest extends TestCase
{
    use RefreshDatabase;

    private const HOTE = 'http://office.localhost';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    protected function tearDown(): void
    {
        // Le téléversement écrit de vrais fichiers : GD ne sait pas travailler
        // sur un disque simulé. On retire ce qu'on a créé.
        foreach (Photo::query()->whereIn('folder', ['annonces', 'destinations'])->get() as $photo) {
            foreach ([800, 1600, 3200] as $w) {
                $chemin = public_path("images/{$photo->folder}/{$photo->key}-{$w}.webp");
                if (is_file($chemin)) {
                    unlink($chemin);
                }
            }
        }

        parent::tearDown();
    }

    private function office(string $chemin): string
    {
        return self::HOTE.$chemin;
    }

    private function connecte(): static
    {
        $admin = Admin::query()->firstOrCreate(['email' => 'equipe@vayla.test'], [
            'name' => 'Voahirana Andria',
            'password' => 'une-phrase-de-passe-assez-longue',
            'password_set_at' => now(),
        ]);

        return $this->actingAs($admin, 'admin');
    }

    /** La fiche telle que l'écran la renvoie, avec quelques champs écrasés. */
    private function fiche(Listing $listing, array $ecrase = []): array
    {
        $listing->load(['amenities', 'categories']);

        return array_merge([
            'title' => $listing->title,
            'destination_id' => $listing->destination_id,
            'kind' => $listing->kind->value,
            'summary' => $listing->summary,
            'description' => $listing->description,
            'guests' => $listing->guests,
            'bedrooms' => $listing->bedrooms,
            'beds' => $listing->beds,
            'bathrooms' => $listing->bathrooms,
            'surface' => $listing->surface,
            'price' => $listing->price,
            'min_nights' => $listing->min_nights,
            'max_nights' => $listing->max_nights,
            'check_in_from' => substr((string) $listing->check_in_from, 0, 5),
            'check_out_before' => substr((string) $listing->check_out_before, 0, 5),
            'pets_allowed' => $listing->pets_allowed,
            'smoking_allowed' => $listing->smoking_allowed,
            'events_allowed' => $listing->events_allowed,
            'featured' => $listing->featured,
            'categories' => $listing->categories->pluck('id')->all(),
            'amenities' => $listing->amenities->map(fn ($a) => ['id' => $a->id, 'highlight' => (bool) $a->pivot->highlight])->all(),
        ], $ecrase);
    }

    // ── Les annonces ────────────────────────────────────────────────────

    /**
     * **Vayla corrige tout, même après vérification** — c'est son rôle —
     * et le journal dit quels champs ont bougé.
     */
    public function test_vayla_corrige_le_contenu_d_une_annonce_verifiee(): void
    {
        $listing = Listing::query()->where('status', ListingStatus::Published->value)->firstOrFail();

        $this->connecte()->post($this->office("/annonces/{$listing->id}/modifier"), $this->fiche($listing, [
            'title' => 'Villa vue lagon, Ambatoloaka',
            'guests' => $listing->guests + 1,
            'price' => 210000,
        ]))->assertSessionHas('succes');

        $listing->refresh();
        $this->assertSame('Villa vue lagon, Ambatoloaka', $listing->title);
        $this->assertSame(210000, (int) $listing->price);

        $ligne = AdminAction::query()->where('kind', AdminActionKind::ListingEdited->value)->firstOrFail();
        $this->assertStringContainsString('titre', $ligne->summary);
        $this->assertStringContainsString('capacité', $ligne->summary);
        $this->assertStringContainsString('tarif', $ligne->summary);
    }

    /** Le niveau et le statut ont leurs gestes à eux : ils n'entrent pas par le contenu. */
    public function test_le_contenu_ne_touche_ni_au_niveau_ni_au_statut_ni_au_slug(): void
    {
        $listing = Listing::query()->where('status', ListingStatus::Published->value)->firstOrFail();
        $avant = [$listing->trust_level, $listing->status, $listing->slug];

        $this->connecte()->post($this->office("/annonces/{$listing->id}/modifier"), $this->fiche($listing, [
            'title' => 'Un tout autre nom de logement',
            'trust_level' => 4,
            'status' => 'draft',
            'slug' => 'nouvelle-adresse',
        ]))->assertSessionHas('succes');

        $listing->refresh();
        $this->assertSame($avant, [$listing->trust_level, $listing->status, $listing->slug]);
    }

    public function test_rien_n_est_ecrit_au_journal_quand_rien_ne_change(): void
    {
        $listing = Listing::query()->firstOrFail();

        $this->connecte()->post($this->office("/annonces/{$listing->id}/modifier"), $this->fiche($listing))
            ->assertSessionHas('succes', 'Rien n’a changé.');

        $this->assertSame(0, AdminAction::query()->count());
    }

    /** « Tout » et « Séjour confirmé » ne se posent sur aucune annonce : ce sont des filtres. */
    public function test_les_categories_structurelles_ne_se_posent_pas(): void
    {
        $listing = Listing::query()->firstOrFail();
        $mer = Category::query()->where('key', 'mer')->firstOrFail();
        $structurelles = Category::query()->whereIn('key', ['all', 'verifie'])->pluck('id')->all();

        $this->connecte()->post($this->office("/annonces/{$listing->id}/modifier"), $this->fiche($listing, [
            'categories' => [$mer->id, ...$structurelles],
        ]));

        $this->assertSame([$mer->id], $listing->categories()->pluck('categories.id')->all());
    }

    public function test_mettre_un_equipement_en_avant_se_lit_au_journal(): void
    {
        $listing = Listing::query()->has('amenities')->firstOrFail();
        $fiche = $this->fiche($listing);
        $fiche['amenities'][0]['highlight'] = ! $fiche['amenities'][0]['highlight'];

        $this->connecte()->post($this->office("/annonces/{$listing->id}/modifier"), $fiche);

        $this->assertStringContainsString('équipements', AdminAction::query()->value('summary'));
    }

    /**
     * Une annonce saisie **pour** un propriétaire naît comme les autres : en
     * brouillon, au niveau 1. La saisir n'est pas la vérifier.
     */
    public function test_saisir_une_annonce_pour_un_proprietaire(): void
    {
        $owner = Owner::query()->where('name', 'like', 'Hanta%')->firstOrFail();
        $modele = $this->fiche(Listing::query()->firstOrFail(), ['title' => 'Maison dictée au téléphone', 'categories' => []]);

        $this->connecte()->post($this->office("/proprietaires/{$owner->id}/annonces"), $modele)
            ->assertRedirect();

        $listing = Listing::query()->where('title', 'Maison dictée au téléphone')->firstOrFail();
        $this->assertSame($owner->id, $listing->owner_id);
        $this->assertSame(ListingStatus::Draft, $listing->status);
        $this->assertSame(TrustLevel::Declared, $listing->trust_level);
        $this->assertTrue(AdminAction::query()->where('kind', AdminActionKind::ListingCreated->value)->exists());
    }

    public function test_les_photos_s_ajoutent_se_rangent_et_se_retirent(): void
    {
        $listing = Listing::query()->firstOrFail();
        $avant = $listing->photos()->count();

        $this->connecte()->post($this->office("/annonces/{$listing->id}/photos"), [
            'photo' => UploadedFile::fake()->image('salon.jpg', 1400, 1050),
        ])->assertSessionHas('succes');

        $nouvelle = Photo::query()->where('folder', 'annonces')->latest('id')->firstOrFail();
        $this->assertSame($avant + 1, $listing->photos()->count());

        // En tête : elle devient la couverture.
        $ids = $listing->photos()->pluck('photos.id')->reject(fn ($id) => $id === $nouvelle->id)->prepend($nouvelle->id)->values()->all();
        $this->connecte()->post($this->office("/annonces/{$listing->id}/photos/ordre"), ['ids' => $ids]);
        $this->assertSame($nouvelle->id, $listing->photos()->first()->id);

        $this->connecte()->post($this->office("/annonces/{$listing->id}/photos/{$nouvelle->id}/retirer"))->assertSessionHas('succes');
        $this->assertSame($avant, $listing->photos()->count());
    }

    /** Une photo d'une autre galerie ne se retire pas depuis celle-ci. */
    public function test_on_ne_retire_pas_la_photo_d_une_autre_annonce(): void
    {
        [$a, $b] = Listing::query()->has('photos')->take(2)->get()->all();
        $photo = $b->photos()->first();

        $this->connecte()->post($this->office("/annonces/{$a->id}/photos/{$photo->id}/retirer"))->assertSessionHas('erreur');
        $this->assertTrue($b->photos()->where('photos.id', $photo->id)->exists());
    }

    // ── Les destinations ────────────────────────────────────────────────

    private function destination(array $ecrase = []): array
    {
        return array_merge([
            'name' => 'Ifaty', 'region' => 'Atsimo-Andrefana', 'tagline' => 'Le lagon du Sud-Ouest et ses baobabs',
            'climate_zone' => 'sud', 'scene' => 'lagoon', 'featured' => false,
        ], $ecrase);
    }

    /** Le slug naît du nom, puis ne bouge plus — même si l'on renomme. */
    public function test_une_destination_garde_son_adresse_quand_on_la_renomme(): void
    {
        $this->connecte()->post($this->office('/destinations'), $this->destination())->assertRedirect();

        $ifaty = Destination::query()->where('slug', 'ifaty')->firstOrFail();

        $this->connecte()->post($this->office("/destinations/{$ifaty->id}"), $this->destination(['name' => 'Ifaty-Mangily', 'slug' => 'autre']))
            ->assertSessionHas('succes');

        $this->assertSame(['ifaty', 'Ifaty-Mangily'], [$ifaty->fresh()->slug, $ifaty->fresh()->name]);
        $this->get('/destinations/ifaty')->assertOk();
    }

    /** @return array<string, mixed> */
    private function credit(array $ecrase = []): array
    {
        return array_merge([
            'photo' => UploadedFile::fake()->image('majunga.jpg', 1600, 1200),
            'caption' => 'Le front de mer de Majunga au coucher du soleil',
            'author' => 'Hery Rakoto',
            'licence' => 'vayla',
            'declaration' => '1',
        ], $ecrase);
    }

    private function majunga(): Destination
    {
        return Destination::query()->where('slug', 'majunga')->firstOrFail();
    }

    /** L'ordre de la galerie, et la couverture recopiée dans `photo_id`. */
    private function galerie(Destination $d): array
    {
        return $d->fresh()->galerie()->pluck('photos.id')->all();
    }

    /**
     * **Une photo de destination se téléverse, avec son crédit**, et arrive au
     * bout de la galerie. Elle va dans `destinations/`, jamais dans `lieux/`,
     * que le catalogue Commons possède.
     */
    public function test_une_photo_de_destination_se_televerse_avec_son_credit(): void
    {
        $majunga = $this->majunga();
        $couverture = $majunga->photo_id;

        $this->connecte()->post($this->office("/destinations/{$majunga->id}/photos"), $this->credit())->assertSessionHas('succes');

        $photo = Photo::query()->where('folder', 'destinations')->latest('id')->firstOrFail();
        $this->assertSame([$couverture, $photo->id], $this->galerie($majunga));
        $this->assertSame($couverture, $majunga->fresh()->photo_id, 'La couverture ne change pas : la nouvelle arrive au bout.');
        $this->assertSame('Hery Rakoto', $photo->author);
        $this->assertFalse($photo->is_ai);
        $this->assertFileExists(public_path("images/destinations/{$photo->key}-800.webp"));
        $this->assertFileExists(public_path("images/destinations/{$photo->key}-1600.webp"));

        // Créditée au pied de page, comme les photographies de Commons.
        $this->get('/')->assertInertia(fn ($page) => $page->where('credits', fn ($c) => collect($c)->contains(fn ($x) => $x['key'] === $photo->key && $x['author'] === 'Hery Rakoto')));
    }

    /** La règle photo se déclare à chaque fois, et une photo floue est refusée. */
    public function test_une_photo_de_destination_se_declare_et_n_est_pas_floue(): void
    {
        $majunga = $this->majunga();
        $avant = $this->galerie($majunga);

        $this->connecte()->post($this->office("/destinations/{$majunga->id}/photos"), $this->credit(['declaration' => null]))
            ->assertSessionHasErrors('declaration');
        $this->connecte()->post($this->office("/destinations/{$majunga->id}/photos"), $this->credit(['author' => '']))
            ->assertSessionHasErrors('author');

        $this->connecte()->post($this->office("/destinations/{$majunga->id}/photos"), $this->credit(['photo' => UploadedFile::fake()->image('floue.jpg', 900, 675)]))
            ->assertSessionHas('erreur');

        $this->assertSame($avant, $this->galerie($majunga));
        $this->assertSame(0, Photo::query()->where('folder', 'destinations')->count());
    }

    /**
     * **Ranger la galerie choisit la couverture** : la première photo est
     * celle de l'atlas et de l'en-tête, et `photo_id` la suit — c'est son seul
     * écrivain. La page publique reçoit la galerie dans cet ordre.
     */
    public function test_ranger_la_galerie_change_la_couverture(): void
    {
        $majunga = $this->majunga();
        $commons = $majunga->photo_id;
        $this->connecte()->post($this->office("/destinations/{$majunga->id}/photos"), $this->credit());
        $nouvelle = Photo::query()->where('folder', 'destinations')->latest('id')->firstOrFail();

        $this->connecte()->post($this->office("/destinations/{$majunga->id}/photos/ordre"), ['ids' => [$nouvelle->id, $commons]])
            ->assertSessionHas('succes');

        $this->assertSame([$nouvelle->id, $commons], $this->galerie($majunga));
        $this->assertSame($nouvelle->id, $majunga->fresh()->photo_id);

        $this->get('/destinations/majunga')->assertInertia(fn ($page) => $page->where('galerie', [$nouvelle->key, Photo::find($commons)->key]));

        // Un identifiant étranger n'entre pas par la porte du rangement.
        $etrangere = Destination::query()->where('slug', '!=', 'majunga')->whereNotNull('photo_id')->value('photo_id');
        $this->connecte()->post($this->office("/destinations/{$majunga->id}/photos/ordre"), ['ids' => [$etrangere, $commons, $nouvelle->id]]);
        $this->assertSame([$commons, $nouvelle->id], $this->galerie($majunga));
    }

    /**
     * Retirer une photo **téléversée** de sa seule galerie l'efface, fichiers
     * compris ; retirer une photographie de Commons la détache seulement.
     */
    public function test_retirer_une_photo_de_la_galerie(): void
    {
        $majunga = $this->majunga();
        $commons = $majunga->photo_id;
        $this->connecte()->post($this->office("/destinations/{$majunga->id}/photos"), $this->credit());
        $photo = Photo::query()->where('folder', 'destinations')->latest('id')->firstOrFail();

        // Encore dans une galerie : la photothèque refuse de la supprimer.
        $this->connecte()->post($this->office("/phototheque/{$photo->id}/retirer"))->assertSessionHas('erreur');

        $this->connecte()->post($this->office("/destinations/{$majunga->id}/photos/{$photo->id}/retirer"))->assertSessionHas('succes');
        $this->assertNull($photo->fresh());
        $this->assertFileDoesNotExist(public_path("images/destinations/{$photo->key}-800.webp"));

        // Commons : détachée, jamais supprimée — et la couverture devient vide.
        $this->connecte()->post($this->office("/destinations/{$majunga->id}/photos/{$commons}/retirer"))->assertSessionHas('succes');
        $this->assertNotNull(Photo::find($commons));
        $this->assertSame([], $this->galerie($majunga));
        $this->assertNull($majunga->fresh()->photo_id);

        // Et elle revient depuis la photothèque, en couverture.
        $this->connecte()->post($this->office("/destinations/{$majunga->id}/photos/ajouter"), ['photo_id' => $commons])->assertSessionHas('succes');
        $this->assertSame($commons, $majunga->fresh()->photo_id);

        $this->connecte()->post($this->office("/phototheque/{$commons}/retirer"))->assertSessionHas('erreur');
    }

    /** Une image générée ou une photo d'annonce n'illustre jamais une destination. */
    public function test_une_destination_n_accepte_que_de_vraies_photos_de_lieux(): void
    {
        $majunga = $this->majunga();
        $avant = $this->galerie($majunga);

        $generee = Photo::query()->where('is_ai', true)->value('id');
        $annonce = Photo::create(['key' => 'test/salon', 'folder' => 'annonces', 'width' => 800, 'is_ai' => false, 'caption' => 'Salon']);

        foreach (array_filter([$generee, $annonce->id]) as $id) {
            $this->connecte()->post($this->office("/destinations/{$majunga->id}/photos/ajouter"), ['photo_id' => $id])->assertSessionHas('erreur');
        }

        $this->assertSame($avant, $this->galerie($majunga));
    }

    /** L'invariant : la couverture de chaque destination est la première de sa galerie. */
    public function test_la_couverture_est_toujours_la_premiere_photo_de_la_galerie(): void
    {
        foreach (Destination::query()->with('galerie')->get() as $d) {
            $this->assertSame($d->galerie->first()?->id, $d->photo_id, "{$d->name} : photo_id et galerie divergent.");
        }
    }

    /**
     * **Un seed ne touche plus aux photos qu'il ne possède pas.** Il effaçait
     * toute photo absente de son catalogue — celles des propriétaires et de
     * l'équipe comprises, fichiers laissés orphelins.
     */
    public function test_un_seed_n_efface_plus_les_photos_televersees(): void
    {
        $majunga = Destination::query()->where('slug', 'majunga')->firstOrFail();
        $this->connecte()->post($this->office("/destinations/{$majunga->id}/photos"), $this->credit());
        $proprio = Photo::create(['key' => 'test/proprio', 'folder' => 'annonces', 'width' => 800, 'is_ai' => false, 'caption' => 'Salon']);

        $this->seed(PhotoSeeder::class);

        $this->assertSame(1, Photo::query()->where('folder', 'destinations')->count());
        $this->assertNotNull($proprio->fresh());
    }

    public function test_une_destination_qui_porte_des_logements_ne_se_supprime_pas(): void
    {
        $pleine = Destination::query()->has('listings')->firstOrFail();
        $vide = Destination::query()->doesntHave('listings')->firstOrFail();

        $this->connecte()->post($this->office("/destinations/{$pleine->id}/supprimer"))->assertSessionHas('erreur');
        $this->assertNotNull($pleine->fresh());

        $this->connecte()->post($this->office("/destinations/{$vide->id}/supprimer"))->assertRedirect($this->office('/destinations'));
        $this->assertNull($vide->fresh());
    }

    // ── Les catégories ──────────────────────────────────────────────────

    public function test_une_categorie_nait_au_bout_du_rail_avec_une_cle_figee(): void
    {
        $this->connecte()->post($this->office('/categories'), ['label' => 'Pieds dans l’eau', 'icon' => 'wave'])->assertSessionHas('succes');

        $nouvelle = Category::query()->where('label', 'Pieds dans l’eau')->firstOrFail();
        $this->assertSame((int) Category::query()->max('position'), $nouvelle->position);

        $cle = $nouvelle->key;
        $this->connecte()->post($this->office("/categories/{$nouvelle->id}"), ['label' => 'Les pieds dans l’eau', 'icon' => 'wave', 'key' => 'autre']);
        $this->assertSame($cle, $nouvelle->fresh()->key);
    }

    /** Une place achetée se dit : le rail public la reçoit, et l'affiche. */
    public function test_une_place_achetee_arrive_jusqu_au_rail_public(): void
    {
        $mer = Category::query()->where('key', 'mer')->firstOrFail();

        $this->connecte()->post($this->office("/categories/{$mer->id}"), ['label' => $mer->label, 'icon' => $mer->icon, 'sponsored' => true]);

        $this->get('/')->assertInertia(fn ($page) => $page->where('categories', fn ($liste) => collect($liste)->firstWhere('key', 'mer')['sponsored'] === true));

        $rail = file_get_contents(resource_path('js/Components/CategoryRail.vue'));
        $this->assertStringContainsString('v-if="c.sponsored"', $rail);
    }

    public function test_les_categories_structurelles_ne_se_suppriment_pas_et_ne_se_vendent_pas(): void
    {
        foreach (['all', 'verifie'] as $cle) {
            $c = Category::query()->where('key', $cle)->firstOrFail();
            $this->connecte()->post($this->office("/categories/{$c->id}/supprimer"))->assertSessionHas('erreur');
            $this->assertNotNull($c->fresh());
        }

        $verifie = Category::query()->where('key', 'verifie')->firstOrFail();
        $this->connecte()->post($this->office("/categories/{$verifie->id}"), ['label' => $verifie->label, 'icon' => 'check', 'sponsored' => true])
            ->assertSessionHas('erreur');
        $this->assertFalse($verifie->fresh()->sponsored);
    }

    public function test_deplacer_une_categorie_echange_sa_place_avec_sa_voisine(): void
    {
        [$premiere, $seconde] = Category::query()->orderBy('position')->take(2)->get()->all();

        $this->connecte()->post($this->office("/categories/{$seconde->id}/deplacer"), ['sens' => 'haut']);

        $this->assertSame(
            [$seconde->id, $premiere->id],
            Category::query()->orderBy('position')->take(2)->pluck('id')->all(),
        );
    }

    /** Les pictogrammes proposés existent tous dans le rail : sinon, retour à l'étincelle. */
    public function test_les_pictogrammes_du_back_office_sont_ceux_du_rail(): void
    {
        $source = file_get_contents(resource_path('js/Support/categoryIcons.js'));
        preg_match_all('/^\s{4}(\w+):/m', $source, $cles);

        $this->assertEqualsCanonicalizing($cles[1], array_keys(OfficeContentReadService::ICONES_CATEGORIES));

        $scene = file_get_contents(resource_path('js/Components/SceneArt.vue'));
        foreach (array_keys(OfficeContentReadService::SCENES) as $variante) {
            $this->assertStringContainsString("{$variante}:", $scene, "SceneArt ne dessine pas « {$variante} ».");
        }
    }

    // ── Les équipements ─────────────────────────────────────────────────

    /**
     * **Un équipement coché ne se supprime pas** : ce serait effacer la
     * déclaration de propriétaires. Un équipement que personne n'a coché, si.
     */
    public function test_un_equipement_coche_ne_se_supprime_pas(): void
    {
        $coche = Amenity::query()->has('listings')->firstOrFail();

        $this->connecte()->post($this->office("/equipements/{$coche->id}/supprimer"))->assertSessionHas('erreur');
        $this->assertNotNull($coche->fresh());

        $this->connecte()->post($this->office('/equipements'), ['label' => 'Hamac sur la terrasse', 'group' => 'exterieur', 'icon' => $coche->icon]);
        $nouveau = Amenity::query()->where('label', 'Hamac sur la terrasse')->firstOrFail();

        $this->connecte()->post($this->office("/equipements/{$nouveau->id}/supprimer"))->assertSessionHas('succes');
        $this->assertNull($nouveau->fresh());
    }

    /** Un pictogramme inventé n'a pas de tracé : on choisit parmi ceux qui existent. */
    public function test_le_pictogramme_d_un_equipement_existe(): void
    {
        $this->connecte()->post($this->office('/equipements'), ['label' => 'Hamac', 'group' => 'exterieur', 'icon' => 'licorne'])
            ->assertSessionHasErrors('icon');
    }

    public function test_renommer_un_equipement_garde_sa_cle(): void
    {
        $wifi = Amenity::query()->firstOrFail();
        $cle = $wifi->key;

        $this->connecte()->post($this->office("/equipements/{$wifi->id}"), [
            'label' => 'Wifi (fibre)', 'group' => $wifi->group->value, 'icon' => $wifi->icon, 'key' => 'autre-cle',
        ])->assertSessionHas('succes');

        $this->assertSame([$cle, 'Wifi (fibre)'], [$wifi->fresh()->key, $wifi->fresh()->label]);
    }

    // ── Les réglages ────────────────────────────────────────────────────

    /** Le taux porte sa date, et tout le site le lit dès l'enregistrement. */
    public function test_le_taux_de_change_se_regle_et_s_affiche_sur_le_site(): void
    {
        $this->connecte()->post($this->office('/reglages/taux'), ['eur_rate' => 4800, 'eur_rate_date' => Carbon::yesterday()->toDateString()])
            ->assertSessionHas('succes');

        $this->get('/')->assertInertia(fn ($page) => $page->where('devise.taux', fn ($t) => (float) $t === 4800.0));
        $this->assertTrue(AdminAction::query()->where('kind', AdminActionKind::SettingChanged->value)->exists());
    }

    public function test_un_taux_de_change_se_date_du_passe(): void
    {
        $this->connecte()->post($this->office('/reglages/taux'), ['eur_rate' => 4800, 'eur_rate_date' => Carbon::tomorrow()->toDateString()])
            ->assertSessionHasErrors('eur_rate_date');
    }

    /**
     * **La commission ne vaut que pour les nouvelles demandes.** Une
     * réservation déjà faite garde le taux du jour : une facture qui change
     * après coup est une facture qu'on ne paie pas.
     */
    public function test_la_commission_ne_change_que_les_nouvelles_demandes(): void
    {
        $ancienne = Booking::query()->firstOrFail();
        $tauxAncien = (float) $ancienne->commission_rate;

        $this->connecte()->post($this->office('/reglages/commission'), ['commission' => 8])->assertSessionHas('succes');

        $this->assertSame($tauxAncien, (float) $ancienne->fresh()->commission_rate);

        $listing = Listing::query()->where('status', ListingStatus::Published->value)->firstOrFail();
        $arrivee = Carbon::today()->addDays(320);
        $nouvelle = app(BookingService::class)->book($listing->load(['unavailabilities', 'bookings']), [
            'traveller' => 'Rakoto', 'traveller_phone' => '+261 34 12 345 67', 'guests' => 1,
            'arrival' => $arrivee->toDateString(),
            'departure' => $arrivee->copy()->addDays(max(2, (int) $listing->min_nights))->toDateString(),
        ]);

        $this->assertSame(0.08, (float) $nouvelle->commission_rate);
        $this->assertSame(BookingStatus::Pending, $nouvelle->status);
    }

    // ── Les seeders ─────────────────────────────────────────────────────

    /** Un `make seed` ne rétablit plus les libellés d'origine : la base fait foi. */
    public function test_un_seed_n_ecrase_pas_ce_que_l_equipe_a_modifie(): void
    {
        Category::query()->where('key', 'mer')->update(['label' => 'La mer, tout simplement']);
        Destination::query()->where('slug', 'nosy-be')->update(['tagline' => 'Une accroche réécrite par l’équipe']);

        $this->seed(CategorySeeder::class);
        $this->seed(DestinationSeeder::class);

        $this->assertSame('La mer, tout simplement', Category::query()->where('key', 'mer')->value('label'));
        $this->assertSame('Une accroche réécrite par l’équipe', Destination::query()->where('slug', 'nosy-be')->value('tagline'));
    }

    // ── L'accès ─────────────────────────────────────────────────────────

    public function test_chaque_ecran_de_contenu_repond(): void
    {
        $listing = Listing::query()->firstOrFail();
        $owner = Owner::query()->firstOrFail();
        $destination = Destination::query()->firstOrFail();

        foreach ([
            "/annonces/{$listing->id}/modifier", "/proprietaires/{$owner->id}/annonces/nouvelle",
            '/destinations', '/destinations/nouvelle', "/destinations/{$destination->id}",
            '/categories', '/equipements', '/reglages',
        ] as $chemin) {
            $this->connecte()->get($this->office($chemin))->assertOk();
        }
    }

    public function test_le_contenu_est_ferme_sans_session_d_equipe(): void
    {
        $owner = Owner::query()->firstOrFail();

        $this->actingAs($owner, 'proprietaire')->get($this->office('/destinations'))->assertRedirect($this->office('/connexion'));
        $this->post($this->office('/reglages/commission'), ['commission' => 25])->assertRedirect($this->office('/connexion'));
    }
}
