<?php

namespace Tests\Feature;

use App\Enums\VerificationKind;
use App\Models\Owner;
use App\Services\Verification\CodeSender;
use App\Services\Verification\VerificationCodeService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le compte propriétaire : **une adresse, un code, et rien d'autre.**
 *
 * L'espace s'est ouvert successivement par une adresse-clé, puis par un numéro
 * de téléphone et un mot de passe. Il s'ouvre maintenant comme celui du
 * voyageur : on saisit son adresse, on reçoit six chiffres, on entre.
 *
 * **L'identifiant est devenu l'adresse, plus le numéro.** L'argument du numéro
 * était que beaucoup de propriétaires n'ont pas de boîte qu'ils relèvent ; il
 * ne tient plus, puisque l'inscription exige désormais une adresse à laquelle
 * un code arrive vraiment — sans elle, aucun compte ne s'ouvre. Le téléphone
 * reste ce par quoi Vayla **appelle** pour la vérification.
 *
 * **Le lien WhatsApp reste, comme chemin court.** Le jour où une demande expire
 * dans quelques heures, aller relever ses mails est un détour de trop. Il ne
 * pose plus de mot de passe — il n'y en a plus — il ouvre l'espace.
 */
class OwnerAuthTest extends TestCase
{
    use RefreshDatabase;

    public static ?string $dernier = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        self::$dernier = null;

        // Un canal de test qui retient le code, à la place d'un vrai envoi.
        $this->app->bind(VerificationCodeService::class, fn () => new VerificationCodeService([
            new class implements CodeSender
            {
                public function envoyer(string $destination, string $code): void
                {
                    OwnerAuthTest::$dernier = $code;
                }

                public function canal(): string
                {
                    return 'test';
                }

                public function sert(VerificationKind $kind): bool
                {
                    return true;
                }
            },
        ]));
    }

    private function hanta(): Owner
    {
        return Owner::query()->where('name', 'like', 'Hanta%')->firstOrFail();
    }

    /** La porte : on entre par l'adresse et le code, sans mot de passe. */
    public function test_on_se_connecte_par_son_adresse_et_un_code(): void
    {
        $hanta = $this->hanta();

        $this->post('/proprietaire/inscription', ['email' => $hanta->email])
            ->assertRedirect('/proprietaire/inscription/code');

        $this->post('/proprietaire/inscription/code', ['code' => self::$dernier])
            ->assertRedirect('/proprietaire');

        $this->assertAuthenticatedAs($hanta, 'proprietaire');

        // Aucun compte n'a été créé au passage : c'est une connexion.
        $this->assertSame(1, Owner::query()->where('email', $hanta->email)->count());
    }

    /**
     * **La porte ne dit jamais si l'adresse est celle d'un propriétaire.**
     *
     * Une réponse différente selon que le compte existe permettrait de tester
     * des adresses une par une pour savoir lesquelles sont sur Vayla.
     */
    public function test_la_porte_ne_revele_pas_les_adresses_connues(): void
    {
        $connue = $this->post('/proprietaire/inscription', ['email' => $this->hanta()->email]);
        $inconnue = $this->post('/proprietaire/inscription', ['email' => 'personne@example.com']);

        $this->assertSame($connue->status(), $inconnue->status());
        $this->assertSame($connue->headers->get('Location'), $inconnue->headers->get('Location'));
    }

    /** L'adresse est reconnue quelle que soit la casse : une boîte, un compte. */
    public function test_l_adresse_est_reconnue_quelle_que_soit_la_casse(): void
    {
        $hanta = $this->hanta();

        $this->post('/proprietaire/inscription', ['email' => mb_strtoupper($hanta->email)]);
        $this->post('/proprietaire/inscription/code', ['code' => self::$dernier])
            ->assertRedirect('/proprietaire');

        $this->assertAuthenticatedAs($hanta, 'proprietaire');
    }

    /** Un mauvais code n'ouvre rien, et ne dit rien du compte. */
    public function test_un_mauvais_code_n_ouvre_pas_l_espace(): void
    {
        $this->post('/proprietaire/inscription', ['email' => $this->hanta()->email]);

        $this->post('/proprietaire/inscription/code', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest('proprietaire');
    }

    public function test_la_deconnexion_ferme_la_session(): void
    {
        $this->actingAs($this->hanta(), 'proprietaire')
            ->post('/proprietaire/deconnexion')
            ->assertRedirect('/proprietaire/connexion');

        $this->assertGuest('proprietaire');
        $this->get('/proprietaire')->assertRedirect('/proprietaire/connexion');
    }

    /**
     * **Le lien WhatsApp ouvre l'espace en un geste.**
     *
     * Il ne pose plus de mot de passe : il n'y en a plus. Il reste le chemin
     * court, celui qu'on prend quand une demande expire dans quelques heures
     * et qu'aller relever sa boîte est un détour de trop.
     */
    public function test_le_lien_d_acces_ouvre_directement_l_espace(): void
    {
        $this->get('/proprietaire/acces/'.$this->hanta()->access_key)
            ->assertRedirect('/proprietaire');

        $this->assertAuthenticatedAs($this->hanta(), 'proprietaire');
    }

    public function test_un_lien_inconnu_renvoie_sur_la_connexion(): void
    {
        $this->get('/proprietaire/acces/cecinestpasunecledacces12345678')
            ->assertRedirect('/proprietaire/connexion')
            ->assertSessionHasErrors('email');

        $this->assertGuest('proprietaire');
    }

    /**
     * **Le lien prouve le numéro, le code prouve l'adresse.**
     *
     * S'être servi du lien envoyé sur WhatsApp démontre qu'on tient cette
     * ligne — c'est exactement ce que prouve un code à usage unique, sans
     * fournisseur de SMS. Entrer par l'adresse ne prouve rien sur le numéro et
     * ne l'écrit donc pas.
     */
    public function test_le_lien_d_acces_prouve_le_numero_pas_le_code(): void
    {
        $hanta = $this->hanta();
        $hanta->forceFill(['phone_verified_at' => null])->save();

        $this->post('/proprietaire/inscription', ['email' => $hanta->email]);
        $this->post('/proprietaire/inscription/code', ['code' => self::$dernier]);
        $this->assertNull($hanta->fresh()->phone_verified_at);

        $this->post('/proprietaire/deconnexion');
        $this->get('/proprietaire/acces/'.$hanta->access_key);

        $this->assertNotNull($hanta->fresh()->phone_verified_at);
    }

    /** Le numéro reste unique : deux comptes indiscernables au téléphone. */
    public function test_deux_proprietaires_ne_partagent_pas_un_numero(): void
    {
        $this->expectException(QueryException::class);

        Owner::create([
            'name' => 'Homonyme',
            'phone' => $this->hanta()->phone,
            'access_key' => Owner::nouvelleCle(),
        ]);
    }

    /**
     * **Plus aucun mot de passe nulle part.**
     *
     * Le test regarde les écrans et les routes : c'est la seule façon de
     * remarquer qu'un formulaire de mot de passe est revenu par une refonte.
     */
    public function test_l_espace_proprietaire_ne_demande_plus_de_mot_de_passe(): void
    {
        foreach (['Owner/Login.vue', 'Owner/Register.vue', 'Owner/Profile.vue'] as $ecran) {
            $source = file_get_contents(resource_path('js/Pages/'.$ecran));

            preg_match('/<template>(.*)<\/template>/s', $source, $balisage);

            $this->assertNotEmpty($balisage, $ecran.' doit avoir un template.');
            $this->assertStringNotContainsString('type="password"', $balisage[1], $ecran);
            $this->assertStringNotContainsString('password', $balisage[1], $ecran);
        }

        $this->get('/proprietaire/mot-de-passe')->assertNotFound();
        $this->get('/proprietaire/mot-de-passe-oublie')->assertNotFound();
    }
}
