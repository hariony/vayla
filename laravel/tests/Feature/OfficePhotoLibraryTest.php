<?php

namespace Tests\Feature;

use App\Enums\AdminActionKind;
use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\Destination;
use App\Models\Photo;
use App\Services\Images\ImageSource;
use App\Services\PhotoService;
use App\Services\PhotoUploadService;
use Database\Seeders\PhotoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;

/**
 * La photothèque du back-office, et le traitement des photos téléversées.
 *
 * Ce que ces tests tiennent :
 *
 * - **chaque photo dit où elle apparaît**, et ce qu'on a le droit d'en faire
 *   dépend de sa provenance — une photo de Commons ne se supprime pas et
 *   garde sa licence ; une photo de démonstration ne se corrige pas ;
 * - **une photo qui n'illustre rien n'est pas créditée** au pied de page ;
 * - **un crédit corrigé survit à `make seed`** ;
 * - **le traitement recadre, redresse et ne recopie jamais l'original** —
 *   une photo démesurée est refusée avant d'être décodée.
 */
class OfficePhotoLibraryTest extends TestCase
{
    use RefreshDatabase;

    private const HOTE = 'http://office.localhost';

    /** @var array<int, string> fichiers écrits hors de la base, à retirer */
    private array $aRetirer = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    protected function tearDown(): void
    {
        // GD ne sait pas écrire sur un disque simulé : on retire ce qu'on a créé.
        foreach (Photo::query()->whereIn('folder', ['annonces', 'destinations'])->get() as $photo) {
            foreach ([800, 1600, 3200] as $w) {
                $this->aRetirer[] = public_path("images/{$photo->folder}/{$photo->key}-{$w}.webp");
            }
        }

        foreach ($this->aRetirer as $chemin) {
            if (is_file($chemin)) {
                unlink($chemin);
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

    private function credit(array $ecrase = []): array
    {
        return array_merge([
            'photo' => UploadedFile::fake()->image('baie.jpg', 1700, 1275),
            'caption' => 'La baie de Diego-Suarez depuis la montagne des Français',
            'author' => 'Hery Rakoto',
            'licence' => 'vayla',
            'declaration' => '1',
        ], $ecrase);
    }

    private function televersee(): Photo
    {
        return Photo::query()->where('folder', 'destinations')->latest('id')->firstOrFail();
    }

    // ── L'écran ─────────────────────────────────────────────────────────

    public function test_la_phototheque_range_les_photos_par_provenance_et_dit_ou_elles_apparaissent(): void
    {
        $majunga = Destination::query()->where('slug', 'majunga')->firstOrFail();

        $this->connecte()->get($this->office('/phototheque?q='.urlencode(Photo::find($majunga->photo_id)->caption)))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Office/Photos/Index')
                ->where('onglets', function ($onglets) {
                    $n = collect($onglets)->pluck('nombre', 'cle');

                    return $n['lieux'] === Photo::query()->where('folder', 'lieux')->where('is_ai', false)
                        ->where('key', 'not like', 'an-%')->where('key', 'not like', 'ia-%')->count()
                        && $n['demonstration'] > 0
                        && $n['televersees'] === 0;
                })
                ->where('photos.0.provenance', 'commons')
                ->where('photos.0.usages', fn ($u) => collect($u)->contains('href', "/destinations/{$majunga->id}"))
                // Commons : la licence ne se change pas, la photo ne se supprime pas.
                ->where('photos.0.peut.licence', false)
                ->where('photos.0.peut.supprimer', false)
                ->where('photos.0.poids', fn ($p) => $p > 0));
    }

    public function test_une_photo_demandee_par_son_adresse_s_ouvre_meme_hors_de_la_page(): void
    {
        $demo = Photo::query()->where('key', 'like', 'an-%')->firstOrFail();

        $this->connecte()->get($this->office("/phototheque?photo={$demo->id}"))
            ->assertInertia(fn ($page) => $page->where('ouverte.id', $demo->id)->where('ouverte.provenance', 'demonstration'));
    }

    // ── Téléverser ──────────────────────────────────────────────────────

    /** Sans destination : elle attend dans la photothèque, **sans être créditée**. */
    public function test_une_photo_se_televerse_sans_destination_et_n_est_pas_creditee(): void
    {
        $this->connecte()->post($this->office('/phototheque'), $this->credit())
            ->assertRedirect()
            ->assertSessionHas('succes');

        $photo = $this->televersee();
        $this->assertSame(1600, $photo->width);
        $this->assertStringStartsWith('la-baie-de-diego-suarez', $photo->key);
        $this->assertFileExists(public_path("images/destinations/{$photo->key}-1600.webp"));
        $this->assertFileDoesNotExist(public_path("images/destinations/{$photo->key}-3200.webp"));

        $credites = collect(app(PhotoService::class)->credits())->pluck('key');
        $this->assertNotContains($photo->key, $credites, 'Une photo qui n’illustre rien ne se crédite pas.');

        $this->assertTrue(AdminAction::query()->where('kind', AdminActionKind::PhotoUploaded->value)->where('subject_id', $photo->id)->exists());
    }

    /** Avec une destination : au bout de sa galerie, et créditée. */
    public function test_une_photo_televersee_pour_une_destination_rejoint_sa_galerie(): void
    {
        $diego = Destination::query()->where('slug', 'diego-suarez')->first() ?? Destination::query()->firstOrFail();
        $avant = $diego->galerie()->count();

        $this->connecte()->post($this->office('/phototheque'), $this->credit(['destination_id' => $diego->id]))->assertSessionHas('succes');

        $photo = $this->televersee();
        $this->assertSame($avant + 1, $diego->galerie()->count());
        $this->assertSame($photo->id, $diego->galerie()->get()->last()->id);
        $this->assertContains($photo->key, collect(app(PhotoService::class)->credits())->pluck('key'));
    }

    // ── Corriger ────────────────────────────────────────────────────────

    public function test_le_credit_d_une_photo_de_l_equipe_se_corrige_licence_comprise(): void
    {
        $this->connecte()->post($this->office('/phototheque'), $this->credit());
        $photo = $this->televersee();

        $this->connecte()->post($this->office("/phototheque/{$photo->id}"), [
            'caption' => 'La baie de Diego-Suarez, vue du nord',
            'author' => 'Hery Rakotomalala',
            'licence' => 'cc-by',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Diego.jpg',
        ])->assertSessionHas('succes');

        $photo->refresh();
        $this->assertSame(['La baie de Diego-Suarez, vue du nord', 'Hery Rakotomalala', 'CC BY 4.0'], [$photo->caption, $photo->author, $photo->licence]);
        $this->assertNotNull($photo->licence_url);
        $this->assertTrue(AdminAction::query()->where('kind', AdminActionKind::PhotoEdited->value)->exists());
    }

    /** Commons : la légende et l'auteur se corrigent, **la licence ne bouge pas**. */
    public function test_la_licence_d_une_photo_de_commons_ne_se_change_pas(): void
    {
        $commons = Photo::query()->where('folder', 'lieux')->where('is_ai', false)->where('key', 'not like', 'an-%')->firstOrFail();
        $licence = $commons->licence;

        $this->connecte()->post($this->office("/phototheque/{$commons->id}"), [
            'caption' => 'Une légende corrigée par l’équipe',
            'author' => $commons->author,
            'licence' => 'vayla',
        ])->assertSessionHas('succes');

        $this->assertSame(['Une légende corrigée par l’équipe', $licence], [$commons->fresh()->caption, $commons->fresh()->licence]);
    }

    public function test_une_photo_de_demonstration_ne_se_corrige_pas(): void
    {
        $demo = Photo::query()->where('key', 'like', 'an-%')->firstOrFail();

        $this->connecte()->post($this->office("/phototheque/{$demo->id}"), ['caption' => 'Autre chose tout à fait'])->assertSessionHas('erreur');
        $this->assertNotSame('Autre chose tout à fait', $demo->fresh()->caption);
    }

    /** Un crédit corrigé au back-office **survit à `make seed`**. */
    public function test_un_credit_corrige_survit_au_seeder(): void
    {
        $commons = Photo::query()->where('folder', 'lieux')->where('key', 'not like', 'an-%')->where('is_ai', false)->firstOrFail();

        $this->connecte()->post($this->office("/phototheque/{$commons->id}"), ['caption' => 'Légende corrigée à la main', 'author' => 'Auteur corrigé']);
        $this->seed(PhotoSeeder::class);

        $this->assertSame(['Légende corrigée à la main', 'Auteur corrigé'], [$commons->fresh()->caption, $commons->fresh()->author]);
    }

    // ── Supprimer ───────────────────────────────────────────────────────

    public function test_seule_une_photo_de_l_equipe_qui_n_illustre_rien_se_supprime(): void
    {
        $majunga = Destination::query()->where('slug', 'majunga')->firstOrFail();

        // Dans une galerie : refusée, avec la raison.
        $this->connecte()->post($this->office('/phototheque'), $this->credit(['destination_id' => $majunga->id]));
        $utilisee = $this->televersee();
        $this->connecte()->post($this->office("/phototheque/{$utilisee->id}/retirer"))->assertSessionHas('erreur');
        $this->assertNotNull($utilisee->fresh());

        // Commons : jamais.
        $this->connecte()->post($this->office("/phototheque/{$majunga->photo_id}/retirer"))->assertSessionHas('erreur');
        $this->assertNotNull(Photo::find($majunga->photo_id));

        // Libre : supprimée, fichiers compris.
        $this->connecte()->post($this->office('/phototheque'), $this->credit());
        $libre = $this->televersee();
        $this->connecte()->post($this->office("/phototheque/{$libre->id}/retirer"))->assertSessionHas('succes');
        $this->assertNull($libre->fresh());
        $this->assertFileDoesNotExist(public_path("images/destinations/{$libre->key}-800.webp"));
        $this->assertTrue(AdminAction::query()->where('kind', AdminActionKind::PhotoDeleted->value)->exists());
    }

    // ── Le traitement ───────────────────────────────────────────────────

    /** Trois paliers, chacun à sa taille, recadrés en 4/3 — jamais agrandis. */
    public function test_les_paliers_sont_recadres_en_quatre_tiers_sans_agrandissement(): void
    {
        $service = app(PhotoUploadService::class);

        $grande = $service->produire(UploadedFile::fake()->image('g.jpg', 3400, 2600), 'destinations', 'test-grande');
        $haute = $service->produire(UploadedFile::fake()->image('h.jpg', 1500, 2400), 'destinations', 'test-haute');

        foreach ([800, 1600, 3200] as $w) {
            $this->aRetirer[] = public_path("images/destinations/test-grande-{$w}.webp");
            $this->aRetirer[] = public_path("images/destinations/test-haute-{$w}.webp");
        }

        $this->assertSame(3200, $grande);
        foreach ([800 => 600, 1600 => 1200, 3200 => 2400] as $l => $h) {
            $this->assertSame([$l, $h], array_slice(getimagesize(public_path("images/destinations/test-grande-{$l}.webp")), 0, 2));
        }

        // En hauteur : 1 500 px de large après recadrage, donc seulement 800.
        $this->assertSame(800, $haute);
        $this->assertSame([800, 600], array_slice(getimagesize(public_path('images/destinations/test-haute-800.webp')), 0, 2));
        $this->assertFileDoesNotExist(public_path('images/destinations/test-haute-1600.webp'));
    }

    /**
     * **Une photo prise à la verticale sort droite.** Le redressement reposait
     * sur l'extension `exif`, absente de l'image PHP : il ne s'était jamais
     * fait. On fabrique une photo couchée, étiquetée « à tourner d'un quart de
     * tour » comme le fait un téléphone : moitié gauche rouge, droite bleue.
     * Redressée, le rouge doit être en haut.
     */
    public function test_l_orientation_du_telephone_est_appliquee(): void
    {
        $brute = imagecreatetruecolor(2400, 1500);
        imagefilledrectangle($brute, 0, 0, 1199, 1499, imagecolorallocate($brute, 220, 20, 20));
        imagefilledrectangle($brute, 1200, 0, 2399, 1499, imagecolorallocate($brute, 20, 20, 220));
        ob_start();
        imagejpeg($brute, null, 90);
        $jpeg = ob_get_clean();

        // Un bloc EXIF minimal : IFD0 avec une seule étiquette, Orientation = 6.
        $tiff = 'II*'."\0".pack('V', 8).pack('v', 1).pack('vvVvv', 0x0112, 3, 1, 6, 0).pack('V', 0);
        $app1 = "\xFF\xE1".pack('n', strlen($tiff) + 8)."Exif\0\0".$tiff;
        $chemin = tempnam(sys_get_temp_dir(), 'vayla').'.jpg';
        file_put_contents($chemin, substr($jpeg, 0, 2).$app1.substr($jpeg, 2));
        $this->aRetirer[] = $chemin;

        $source = ImageSource::ouvrir($chemin);
        $this->assertSame([1500, 2400], [$source->largeur, $source->hauteur], 'Lue redressée : en hauteur.');

        $cle = 'test-orientation';
        foreach ([800, 1600, 3200] as $w) {
            $this->aRetirer[] = public_path("images/destinations/{$cle}-{$w}.webp");
        }
        app(PhotoUploadService::class)->produire(new UploadedFile($chemin, 'couchee.jpg', 'image/jpeg', null, true), 'destinations', $cle);

        $sortie = imagecreatefromwebp(public_path("images/destinations/{$cle}-800.webp"));
        $haut = imagecolorsforindex($sortie, imagecolorat($sortie, 400, 60));
        $bas = imagecolorsforindex($sortie, imagecolorat($sortie, 400, 540));

        $this->assertGreaterThan($haut['blue'], $haut['red'], 'Le rouge (la gauche de la photo couchée) doit finir en haut.');
        $this->assertGreaterThan($bas['red'], $bas['blue']);
    }

    /** Une photo démesurée est refusée **avant** d'être décodée, avec sa taille. */
    public function test_une_photo_demesuree_est_refusee_sans_etre_decodee(): void
    {
        // Un PNG dont l'en-tête annonce 10 000 × 9 000 px : 90 mégapixels.
        $ihdr = pack('NNCCCCC', 10000, 9000, 8, 2, 0, 0, 0);
        $png = "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.$ihdr.pack('N', crc32('IHDR'.$ihdr));
        $chemin = tempnam(sys_get_temp_dir(), 'vayla').'.png';
        file_put_contents($chemin, $png);
        $this->aRetirer[] = $chemin;

        try {
            app(PhotoUploadService::class)->produire(new UploadedFile($chemin, 'enorme.png', 'image/png', null, true), 'destinations', 'test-enorme');
            $this->fail('Une photo de 90 mégapixels aurait dû être refusée.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('90 mégapixels', $e->getMessage());
        }
    }
}
