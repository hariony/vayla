<?php

namespace Tests\Feature;

use App\Contracts\Repositories\VerificationCodeRepositoryInterface;
use App\Contracts\Verification\CodeSender;
use App\Enums\VerificationKind;
use App\Exceptions\CodeSendingFailed;
use App\Exceptions\CodeThrottled;
use App\Mail\CodeMail;
use App\Models\VerificationCode;
use App\Providers\AppServiceProvider;
use App\Services\Verification\VerificationCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Le code à usage unique, e-mail comme téléphone.
 *
 * **Le code lui-même est trivial ; tout ce qui compte est autour.** Ces tests
 * ne vérifient pas qu'on sait tirer six chiffres — ils vérifient les cinq
 * bornes qui font la différence entre un OTP et un théâtre de sécurité :
 * hachage en base, essais comptés, essais comptés **par code**, délai de
 * renvoi, plafond horaire.
 *
 * Elles sont écrites une fois et valent pour les deux canaux : les recopier
 * par canal aurait garanti qu'une des deux versions finisse par mentir. Les
 * tests le vérifient en jouant les mêmes règles sur une adresse et sur un
 * numéro.
 */
class VerificationCodeTest extends TestCase
{
    use RefreshDatabase;

    private string $numero = '+261 34 85 311 20';

    private string $adresse = 'hanta@example.com';

    protected function setUp(): void
    {
        parent::setUp();

        $this->capturer();
    }

    /** Un canal de test qui retient le code, à la place d'un vrai envoi. */
    private function capturer(): void
    {
        $this->app->bind(VerificationCodeService::class, fn () => new VerificationCodeService([
            new class implements CodeSender
            {
                public function envoyer(string $destination, string $code): void
                {
                    VerificationCodeTest::$dernier = $code;
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
        ], app(VerificationCodeRepositoryInterface::class)));
    }

    public static ?string $dernier = null;

    private function service(): VerificationCodeService
    {
        return app(VerificationCodeService::class);
    }

    private function dernierCode(): string
    {
        return self::$dernier;
    }

    public function test_un_code_envoye_se_verifie(): void
    {
        $this->service()->demander(VerificationKind::Phone, $this->numero);

        $this->assertTrue($this->service()->verifier(VerificationKind::Phone, $this->numero, $this->dernierCode()));
        $this->assertTrue($this->service()->estVerifiee(VerificationKind::Phone, $this->numero));
    }

    /** Six chiffres, zéros de tête compris : le champ se tape, il ne se dicte pas. */
    public function test_le_code_fait_six_chiffres(): void
    {
        $this->service()->demander(VerificationKind::Phone, $this->numero);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->dernierCode());
    }

    /**
     * **Le code n'est jamais en clair en base.** Une base copiée ne doit pas
     * livrer des codes vivants : six chiffres se rejouent en une seconde.
     */
    public function test_le_code_est_hache_en_base(): void
    {
        $ligne = $this->service()->demander(VerificationKind::Phone, $this->numero);
        $code = $this->dernierCode();

        $this->assertNotSame($code, $ligne->code_hash);
        $this->assertTrue(Hash::check($code, $ligne->code_hash));
        // Ni dans une sérialisation, même par mégarde.
        $this->assertArrayNotHasKey('code_hash', $ligne->fresh()->toArray());
    }

    /** Le numéro est normalisé : on demande en international, on vérifie en national. */
    public function test_le_code_se_verifie_quelle_que_soit_l_ecriture_du_numero(): void
    {
        $this->service()->demander(VerificationKind::Phone, '+261 34 85 311 20');

        $this->assertTrue($this->service()->verifier(
            VerificationKind::Phone,
            '0348531120',
            $this->dernierCode()
        ));
    }

    public function test_un_mauvais_code_est_refuse(): void
    {
        $this->service()->demander(VerificationKind::Phone, $this->numero);

        $this->assertFalse($this->service()->verifier(VerificationKind::Phone, $this->numero, '000000'));
        $this->assertFalse($this->service()->estVerifiee(VerificationKind::Phone, $this->numero));
    }

    /**
     * **Cinq essais, puis le code meurt.** Un million de combinaisons et cinq
     * tirages : le hasard est hors de portée.
     */
    public function test_le_code_meurt_apres_cinq_essais(): void
    {
        $this->service()->demander(VerificationKind::Phone, $this->numero);
        $bon = $this->dernierCode();

        for ($i = 0; $i < 5; $i++) {
            $this->service()->verifier(VerificationKind::Phone, $this->numero, '000000');
        }

        $this->assertFalse($this->service()->verifier(VerificationKind::Phone, $this->numero, $bon),
            'Le bon code ne doit plus passer une fois les essais épuisés.');
    }

    public function test_un_code_expire_ne_passe_plus(): void
    {
        $this->service()->demander(VerificationKind::Phone, $this->numero);
        $code = $this->dernierCode();

        $this->travel(11)->minutes();

        $this->assertFalse($this->service()->verifier(VerificationKind::Phone, $this->numero, $code));
    }

    /** Un code ne sert qu'une fois : rejouer le même n'ouvre rien. */
    public function test_un_code_ne_sert_qu_une_fois(): void
    {
        $this->service()->demander(VerificationKind::Phone, $this->numero);
        $code = $this->dernierCode();

        $this->assertTrue($this->service()->verifier(VerificationKind::Phone, $this->numero, $code));
        $this->assertFalse($this->service()->verifier(VerificationKind::Phone, $this->numero, $code));
    }

    /**
     * **Demander un code annule le précédent.** Deux codes vivants doublent
     * les chances d'un tirage au sort, et celui qui en a reçu deux essaie le
     * mauvais et croit le service cassé.
     */
    public function test_un_nouveau_code_annule_l_ancien(): void
    {
        $this->service()->demander(VerificationKind::Phone, $this->numero);
        $ancien = $this->dernierCode();

        $this->travel(61)->seconds();
        $this->service()->demander(VerificationKind::Phone, $this->numero);
        $nouveau = $this->dernierCode();

        $this->assertNotSame($ancien, $nouveau);
        $this->assertFalse($this->service()->verifier(VerificationKind::Phone, $this->numero, $ancien));
        $this->assertTrue($this->service()->verifier(VerificationKind::Phone, $this->numero, $nouveau));
    }

    /** Sans délai, le bouton « renvoyer » devient un distributeur de messages payants. */
    public function test_on_ne_redemande_pas_un_code_tout_de_suite(): void
    {
        $this->service()->demander(VerificationKind::Phone, $this->numero);

        $this->expectException(CodeThrottled::class);
        $this->service()->demander(VerificationKind::Phone, $this->numero);
    }

    public function test_le_delai_de_renvoi_se_lit_en_secondes(): void
    {
        $this->service()->demander(VerificationKind::Phone, $this->numero);

        $this->assertGreaterThan(0, $this->service()->attenteAvantRenvoi(VerificationKind::Phone, $this->numero));

        $this->travel(61)->seconds();

        $this->assertSame(0, $this->service()->attenteAvantRenvoi(VerificationKind::Phone, $this->numero));
    }

    /**
     * **Le plafond horaire est la borne qui compte.** Sans elle, un inconnu
     * fait payer à Vayla vingt messages sur le téléphone de quelqu'un d'autre.
     */
    public function test_le_nombre_de_codes_par_heure_est_plafonne(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->service()->demander(VerificationKind::Phone, $this->numero);
            $this->travel(61)->seconds();
        }

        $this->expectException(CodeThrottled::class);
        $this->service()->demander(VerificationKind::Phone, $this->numero);
    }

    /**
     * **Un code qui n'est pas parti ne reste pas jouable** : il occuperait le
     * quota horaire et bloquerait la vraie tentative suivante.
     */
    public function test_un_envoi_qui_echoue_ne_laisse_pas_de_code_vivant(): void
    {
        $this->app->bind(VerificationCodeService::class, fn () => new VerificationCodeService([
            new class implements CodeSender
            {
                public function envoyer(string $destination, string $code): void
                {
                    throw new CodeSendingFailed('Le message n’a pas pu partir.');
                }

                public function canal(): string
                {
                    return 'cassé';
                }

                public function sert(VerificationKind $kind): bool
                {
                    return true;
                }
            },
        ], app(VerificationCodeRepositoryInterface::class)));

        try {
            $this->service()->demander(VerificationKind::Phone, $this->numero);
            $this->fail('L’échec d’envoi doit remonter.');
        } catch (CodeSendingFailed) {
            // attendu
        }

        $ligne = VerificationCode::query()->latest('id')->firstOrFail();
        $this->assertTrue($ligne->expire(), 'Le code d’un envoi manqué doit être mort.');
    }

    /** Deux numéros ne partagent pas leurs codes. */
    public function test_le_code_d_un_numero_n_ouvre_pas_un_autre(): void
    {
        $autre = '+261 34 00 000 09';

        $this->service()->demander(VerificationKind::Phone, $this->numero);
        $code = $this->dernierCode();

        $this->assertFalse($this->service()->verifier(VerificationKind::Phone, $autre, $code));
    }

    /** La preuve ne vaut qu'une heure : un numéro vérifié hier ne l'est plus. */
    public function test_la_verification_ne_vaut_pas_indefiniment(): void
    {
        $this->service()->demander(VerificationKind::Phone, $this->numero);
        $this->service()->verifier(VerificationKind::Phone, $this->numero, $this->dernierCode());

        $this->assertTrue($this->service()->estVerifiee(VerificationKind::Phone, $this->numero));

        $this->travel(2)->hours();

        $this->assertFalse($this->service()->estVerifiee(VerificationKind::Phone, $this->numero));
    }

    /**
     * **Le canal par défaut n'envoie rien de réel.** Envoyer pour de vrai doit
     * être un choix explicite, jamais le résultat d'un oubli de configuration :
     * sinon la mise au point d'un écran part sur de vrais téléphones, et se
     * facture.
     */
    /**
     * **Le canal téléphone par défaut n'envoie rien de réel.** Envoyer pour de
     * vrai doit être un choix explicite, jamais le résultat d'un oubli de
     * configuration : sinon la mise au point d'un écran part sur de vrais
     * téléphones, et se facture.
     */
    public function test_le_canal_telephone_par_defaut_est_le_journal(): void
    {
        config(['vayla.otp.driver' => null]);
        $this->app->register(AppServiceProvider::class, force: true);

        $ligne = app(VerificationCodeService::class)->demander(VerificationKind::Phone, $this->numero);

        $this->assertSame('log', $ligne->channel);
    }

    /**
     * **L'e-mail a son propre canal, indépendant du pilote téléphone.** C'est
     * ce qui permet d'inscrire par courrier aujourd'hui alors que WhatsApp
     * attend une entreprise vérifiée.
     */
    public function test_l_email_passe_par_le_courrier(): void
    {
        config(['vayla.otp.driver' => 'whatsapp']);
        $this->app->register(AppServiceProvider::class, force: true);
        Mail::fake();

        $ligne = app(VerificationCodeService::class)->demander(VerificationKind::Email, $this->adresse);

        $this->assertSame('mail', $ligne->channel);
        Mail::assertSent(CodeMail::class);
    }

    /**
     * **Les cinq bornes valent pour l'adresse comme pour le numéro.** C'est
     * tout l'objet de n'avoir qu'un service : les recopier par canal aurait
     * garanti qu'une des deux versions finisse par mentir.
     */
    public function test_les_memes_bornes_s_appliquent_a_une_adresse(): void
    {
        $this->service()->demander(VerificationKind::Email, $this->adresse);
        $code = $this->dernierCode();

        $this->assertFalse($this->service()->verifier(VerificationKind::Email, $this->adresse, '000000'));
        $this->assertTrue($this->service()->verifier(VerificationKind::Email, $this->adresse, $code));
        $this->assertTrue($this->service()->estVerifiee(VerificationKind::Email, $this->adresse));

        $this->expectException(CodeThrottled::class);
        $this->service()->demander(VerificationKind::Email, $this->adresse);
    }

    /**
     * `Jean@Gmail.com` et `jean@gmail.com` sont la même adresse : sans
     * normalisation, ce seraient deux quotas distincts, et un code vérifié sur
     * l'un n'ouvrirait pas l'autre.
     */
    public function test_l_adresse_est_normalisee(): void
    {
        $this->service()->demander(VerificationKind::Email, 'Hanta@Example.COM');

        $this->assertTrue($this->service()->verifier(
            VerificationKind::Email, ' hanta@example.com ', $this->dernierCode()
        ));
    }

    public function test_la_commande_envoie_et_verifie(): void
    {
        $this->artisan('vayla:code', ['destination' => '0348531120'])->assertSuccessful();

        $this->artisan('vayla:code', ['destination' => '0348531120', '--code' => '000000'])->assertFailed();
        $this->artisan('vayla:code', ['destination' => '0348531120', '--code' => $this->dernierCode()])
            ->assertSuccessful();
    }

    /** Une destination qui contient un « @ » part par courrier, sans qu'on le dise. */
    public function test_la_commande_reconnait_une_adresse(): void
    {
        $this->artisan('vayla:code', ['destination' => 'hanta@example.com'])->assertSuccessful();
        $this->artisan('vayla:code', ['destination' => 'hanta@example.com', '--code' => $this->dernierCode()])
            ->assertSuccessful();
    }

    public function test_la_commande_refuse_un_numero_inutilisable(): void
    {
        $this->artisan('vayla:code', ['destination' => 'bonjour'])->assertFailed();
    }
}
