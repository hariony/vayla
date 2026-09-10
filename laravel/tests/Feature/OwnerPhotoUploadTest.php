<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Owner;
use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Le téléversement des photos d'une annonce.
 *
 * Trois choses à protéger, et aucune n'est cosmétique :
 *
 * 1. **Les photos de propriétaire ne se mélangent pas à celles de Commons.**
 *    Ces dernières vivent dans `images/lieux/` avec auteur, licence et page
 *    source — c'est ce qu'exigent CC BY et CC BY-SA. Confondre les deux ferait
 *    apparaître les photos d'un propriétaire dans le bloc « Crédits photo » du
 *    pied de page, et `PhotoFilesTest` les signalerait comme orphelines.
 * 2. **Jamais d'agrandissement.** `photos.width` doit dire la vérité sur ce
 *    que porte le disque : un `srcset` qui promettrait un fichier inexistant
 *    ferait télécharger un 404, et sur une connexion malgache un aller-retour
 *    perdu se paie cher.
 * 3. **La position 0 est la couverture**, et rien d'autre ne la désigne.
 */
class OwnerPhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    private string $dossier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->dossier = public_path('images/annonces');
    }

    protected function tearDown(): void
    {
        // Le test écrit de vrais fichiers : GD ne sait pas travailler sur un
        // disque simulé. On nettoie ce qu'on a créé.
        foreach (Photo::query()->where('folder', 'annonces')->get() as $photo) {
            foreach ([800, 1600, 3200] as $w) {
                $chemin = "{$this->dossier}/{$photo->key}-{$w}.webp";
                if (is_file($chemin)) {
                    unlink($chemin);
                }
            }
        }

        parent::tearDown();
    }

    private function hanta(): Owner
    {
        return Owner::query()->where('name', 'like', 'Hanta%')->firstOrFail();
    }

    private function villa(): Listing
    {
        return Listing::query()->where('slug', 'villa-ambatoloaka')->firstOrFail();
    }

    private function image(int $largeur, int $hauteur): UploadedFile
    {
        return UploadedFile::fake()->image('salon.jpg', $largeur, $hauteur);
    }

    private function envoyer(UploadedFile $fichier): TestResponse
    {
        return $this->actingAs($this->hanta(), 'proprietaire')
            ->post('/proprietaire/logements/villa-ambatoloaka/photos', ['photo' => $fichier]);
    }

    public function test_une_photo_produit_ses_paliers_et_va_dans_annonces(): void
    {
        $this->envoyer($this->image(2400, 1800))->assertRedirect()->assertSessionHas('succes');

        $photo = Photo::query()->where('folder', 'annonces')->latest('id')->firstOrFail();

        $this->assertSame(1600, $photo->width, 'On ne dépasse jamais la taille de l’original.');
        $this->assertFileExists("{$this->dossier}/{$photo->key}-800.webp");
        $this->assertFileExists("{$this->dossier}/{$photo->key}-1600.webp");
        $this->assertFileDoesNotExist("{$this->dossier}/{$photo->key}-3200.webp");

        // Aucun crédit : la photo est au propriétaire, il n'y a rien à citer.
        $this->assertNull($photo->author);
        $this->assertNull($photo->licence);
    }

    /** Une photo trop petite est floue sur la photo de tête : on refuse en disant la taille. */
    public function test_une_photo_trop_petite_est_refusee_avec_sa_taille(): void
    {
        $this->envoyer($this->image(900, 700))->assertRedirect()->assertSessionHas('erreur');

        $this->assertStringContainsString('900', session('erreur'));
        $this->assertSame(0, Photo::query()->where('folder', 'annonces')->count());
    }

    public function test_la_photo_rejoint_la_galerie_en_derniere_position(): void
    {
        $avant = $this->villa()->photos()->count();

        $this->envoyer($this->image(2000, 1500));

        $galerie = $this->villa()->fresh()->photos;

        $this->assertCount($avant + 1, $galerie);
        $this->assertSame($avant, $galerie->last()->pivot->position);
    }

    /** La position 0 est la couverture : réordonner suffit, il n'y a pas d'autre bouton. */
    public function test_reordonner_change_la_couverture(): void
    {
        $villa = $this->villa();
        $ordre = $villa->photos->sortBy('pivot.position')->pluck('id')->all();
        $this->assertGreaterThan(1, count($ordre));

        $inverse = array_reverse($ordre);

        $this->actingAs($this->hanta(), 'proprietaire')
            ->post('/proprietaire/logements/villa-ambatoloaka/photos/ordre', ['ids' => $inverse])
            ->assertRedirect();

        $this->assertSame(
            $inverse[0],
            $villa->fresh()->photos->sortBy('pivot.position')->first()->id
        );
    }

    /** Un identifiant venu d'ailleurs ne doit pas entrer dans la galerie par le réordonnancement. */
    public function test_un_identifiant_etranger_n_entre_pas_dans_la_galerie(): void
    {
        $villa = $this->villa();
        $etrangere = Photo::query()->whereNotIn('id', $villa->photos->pluck('id'))->firstOrFail();
        $avant = $villa->photos->count();

        $this->actingAs($this->hanta(), 'proprietaire')
            ->post('/proprietaire/logements/villa-ambatoloaka/photos/ordre',
                ['ids' => [$etrangere->id, ...$villa->photos->pluck('id')->all()]])
            ->assertRedirect();

        $this->assertCount($avant, $villa->fresh()->photos);
    }

    public function test_retirer_une_photo_de_proprietaire_efface_ses_fichiers(): void
    {
        $this->envoyer($this->image(2000, 1500));
        $photo = Photo::query()->where('folder', 'annonces')->latest('id')->firstOrFail();
        $chemin = "{$this->dossier}/{$photo->key}-800.webp";

        $this->actingAs($this->hanta(), 'proprietaire')
            ->post("/proprietaire/logements/villa-ambatoloaka/photos/{$photo->id}/retirer")
            ->assertRedirect();

        $this->assertFileDoesNotExist($chemin);
        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
    }

    /**
     * Une photographie de Commons peut illustrer une destination **et** une
     * annonce, avec un seul crédit : la retirer d'une galerie ne doit jamais
     * supprimer le fichier ni la ligne.
     */
    public function test_retirer_une_photo_de_commons_ne_la_detruit_pas(): void
    {
        $villa = $this->villa();
        $photo = $villa->photos->first();

        $this->actingAs($this->hanta(), 'proprietaire')
            ->post("/proprietaire/logements/villa-ambatoloaka/photos/{$photo->id}/retirer")
            ->assertRedirect();

        $this->assertDatabaseHas('photos', ['id' => $photo->id]);
        $this->assertFileExists(public_path("images/lieux/{$photo->key}-800.webp"));
    }

    public function test_on_ne_televerse_pas_sur_l_annonce_d_un_autre(): void
    {
        $this->actingAs($this->hanta(), 'proprietaire')
            ->post('/proprietaire/logements/front-de-mer-amborovy/photos', ['photo' => $this->image(2000, 1500)])
            ->assertNotFound();
    }

    public function test_un_fichier_qui_n_est_pas_une_image_est_refuse(): void
    {
        $this->actingAs($this->hanta(), 'proprietaire')
            ->post('/proprietaire/logements/villa-ambatoloaka/photos', [
                'photo' => UploadedFile::fake()->create('contrat.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHasErrors('photo');
    }
}
