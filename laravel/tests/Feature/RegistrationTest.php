<?php

namespace Tests\Feature;

use App\Enums\VerificationKind;
use App\Models\Listing;
use App\Models\Owner;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\Verification\CodeSender;
use App\Services\Verification\VerificationCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * L'inscription par adresse e-mail, pour le voyageur comme pour le
 * propriétaire.
 *
 * **L'e-mail est le canal parce que c'est le seul qui part automatiquement**
 * sans entreprise enregistrée ni carte bancaire. WhatsApp et le SMS attendent
 * derrière la même interface.
 *
 * **Une adresse, puis un code. Rien d'autre.** Le voyageur n'a plus de mot de
 * passe du tout : le code reçu prouve l'adresse à chaque connexion, ce qu'un
 * mot de passe ne fait jamais — il prouve seulement qu'on connaît une chaîne.
 * Le propriétaire en garde un, parce que son identifiant de connexion est son
 * **téléphone** et qu'il revient toutes les semaines, souvent pour répondre à
 * une demande qui expire : attendre un code à chaque fois le mettrait dehors
 * au pire moment.
 *
 * Quatre choses à protéger, et la première est celle qu'on oublie :
 *
 * 1. **Le compte n'existe qu'après le code.** Créer une ligne « non vérifiée »
 *    à la première étape permettrait de **squatter l'adresse de quelqu'un
 *    d'autre**, qui se verrait ensuite refuser son inscription.
 * 2. **La même porte inscrit et connecte.** C'est le code qui décide : le
 *    compte existe, on entre ; il n'existe pas, il s'ouvre. Faire choisir
 *    avant, c'est demander de trancher une question dont beaucoup n'ont pas la
 *    réponse.
 * 3. **La porte ne dit jamais si l'adresse est connue.** Une réponse
 *    différente selon que le compte existe permettrait de tester des adresses
 *    une par une pour savoir lesquelles sont sur Vayla.
 * 4. **S'inscrire n'est pas publier.** Un propriétaire naît au niveau 1 ; c'est
 *    l'appel de vérification qui met en ligne.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public static ?string $dernier = null;

    protected function setUp(): void
    {
        parent::setUp();

        self::$dernier = null;

        // Un canal de test qui retient le code, à la place d'un vrai envoi.
        $this->app->bind(VerificationCodeService::class, fn () => new VerificationCodeService([
            new class implements CodeSender
            {
                public function envoyer(string $destination, string $code): void
                {
                    RegistrationTest::$dernier = $code;
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

    /** @return array<string, string> */
    private function voyageur(array $ecrase = []): array
    {
        return array_merge(['email' => 'claire@example.com'], $ecrase);
    }

    /** @return array<string, string> */
    private function proprietaire(array $ecrase = []): array
    {
        // Une adresse **absente du seeder** : « hanta@example.com » y est
        // désormais, et le code connecterait au lieu de créer.
        return array_merge(['email' => 'nouvelle-proprio@example.com'], $ecrase);
    }

    /** La fiche du propriétaire, saisie après le code. */
    private function fiche(array $ecrase = []): array
    {
        return array_merge([
            'name' => 'Hanta Rasoa',
            'phone' => '034 12 345 67',
        ], $ecrase);
    }

    /** Le parcours complet du propriétaire, jusqu'au compte ouvert. */
    private function inscrireProprietaire(array $fiche = []): void
    {
        $this->post('/proprietaire/inscription', $this->proprietaire());
        $this->post('/proprietaire/inscription/code', ['code' => self::$dernier]);
        $this->post('/proprietaire/inscription/fiche', $this->fiche($fiche));
    }

    // ── Voyageur ─────────────────────────────────────────────────────

    public function test_un_voyageur_s_inscrit_en_deux_temps(): void
    {
        $this->post('/inscription', $this->voyageur())->assertRedirect('/inscription/code');

        // **Rien en base tant que le code n'est pas saisi.**
        $this->assertSame(0, User::count());

        $this->post('/inscription/code', ['code' => self::$dernier])
            ->assertRedirect('/mes-reservations');

        $user = User::query()->firstOrFail();
        $this->assertSame('claire@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
        // Ni nom ni mot de passe : le nom vient à la demande de séjour, où il
        // sert, et le mot de passe n'existe plus.
        $this->assertNull($user->name);
        $this->assertNull($user->password);
        $this->assertAuthenticatedAs($user);
    }

    /**
     * **Le test qui protège les adresses des autres.** Une ligne créée avant
     * la vérification permettrait de bloquer l'inscription de quelqu'un en
     * s'inscrivant avec son adresse.
     */
    public function test_une_inscription_abandonnee_ne_bloque_pas_l_adresse(): void
    {
        $this->post('/inscription', $this->voyageur());

        // Rien en base : l'adresse reste disponible pour son vrai
        // propriétaire.
        $this->assertSame(0, User::query()->where('email', 'claire@example.com')->count());

        // On reprend la même adresse et on va au bout : aucune règle
        // d'unicité ne s'y oppose, puisque rien n'avait été écrit.
        $this->travel(61)->seconds();
        $this->post('/inscription', $this->voyageur())->assertRedirect('/inscription/code');
        $this->post('/inscription/code', ['code' => self::$dernier])->assertRedirect('/mes-reservations');

        $this->assertSame(1, User::count());
    }

    public function test_un_mauvais_code_ne_cree_rien_et_garde_la_saisie(): void
    {
        $this->post('/inscription', $this->voyageur());

        $this->post('/inscription/code', ['code' => '000000'])->assertSessionHasErrors('code');

        $this->assertSame(0, User::count());
        // La session est conservée : une faute de frappe ne doit pas obliger
        // à tout ressaisir.
        $this->get('/inscription/code')->assertOk();
    }

    public function test_l_ecran_de_code_renvoie_au_formulaire_sans_inscription_en_cours(): void
    {
        $this->get('/inscription/code')->assertRedirect('/inscription');
    }

    /**
     * **Une adresse déjà connue n'est pas refusée : elle connecte.**
     *
     * C'est tout le principe de la porte unique. La refuser obligerait à
     * savoir, avant de taper, si on s'est inscrit un jour — et beaucoup ne le
     * savent pas. Le code tranche à leur place, et aucun compte n'est créé en
     * double.
     */
    public function test_une_adresse_deja_connue_connecte_au_lieu_de_refuser(): void
    {
        $existant = User::create(['email' => 'claire@example.com']);

        $this->post('/inscription', $this->voyageur())->assertRedirect('/inscription/code');
        $this->post('/inscription/code', ['code' => self::$dernier])->assertRedirect('/mes-reservations');

        $this->assertSame(1, User::count());
        $this->assertAuthenticatedAs($existant->fresh());
    }

    /**
     * **La porte ne dit jamais si l'adresse a un compte.**
     *
     * Une réponse différente selon que le compte existe permettrait de tester
     * des adresses une par une pour savoir lesquelles sont sur Vayla — le nom
     * et le téléphone d'un voyageur sont derrière.
     */
    public function test_la_porte_ne_revele_pas_les_adresses_connues(): void
    {
        User::create(['email' => 'connue@example.com']);

        $connue = $this->post('/inscription', ['email' => 'connue@example.com']);
        $inconnue = $this->post('/inscription', ['email' => 'inconnue@example.com']);

        $this->assertSame($connue->status(), $inconnue->status());
        $this->assertSame($connue->headers->get('Location'), $inconnue->headers->get('Location'));
    }

    public function test_un_voyageur_se_reconnecte_par_le_code(): void
    {
        $this->seed();
        $this->post('/inscription', $this->voyageur());
        $this->post('/inscription/code', ['code' => self::$dernier]);
        $this->post('/deconnexion');

        // La reconnexion emprunte la même porte : une adresse, un code.
        $this->travel(61)->seconds();
        $this->post('/inscription', $this->voyageur())->assertRedirect('/inscription/code');
        $this->post('/inscription/code', ['code' => self::$dernier])->assertRedirect('/mes-reservations');

        // Et toujours un seul compte.
        $this->assertSame(1, User::count());

        $this->get('/mes-reservations')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Bookings')
                ->where('traveller.email', 'claire@example.com')
                ->has('bookings'));
    }

    /**
     * **Un mauvais code ne connecte pas**, et il ne dit pas non plus si
     * l'adresse existe : le message porte sur le code, jamais sur le compte.
     */
    public function test_un_mauvais_code_ne_connecte_pas(): void
    {
        User::create(['email' => 'claire@example.com']);

        $this->post('/inscription', $this->voyageur());
        $this->post('/inscription/code', ['code' => '000000'])->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    /** Les séjours se rattachent par l'adresse, sans recollage manuel. */
    public function test_les_reservations_se_rattachent_par_l_adresse(): void
    {
        $this->seed();

        $listing = Listing::query()->where('slug', 'villa-ambatoloaka')->firstOrFail();
        $this->post("/logements/{$listing->slug}/reserver", [
            'traveller' => 'Claire Fontaine',
            'traveller_phone' => '+33 6 12 45 78 90',
            'traveller_email' => 'claire@example.com',
            'guests' => 2,
            'arrival' => now()->addDays(280)->toDateString(),
            'departure' => now()->addDays(284)->toDateString(),
        ])->assertRedirect();

        $this->post('/inscription', $this->voyageur());
        $this->post('/inscription/code', ['code' => self::$dernier]);

        $this->get('/mes-reservations')
            ->assertInertia(fn ($page) => $page->has('bookings', 1));
    }

    /**
     * **Les redirections d'authentification dépendent de la porte.**
     *
     * Elles pointaient toutes les deux sur l'espace propriétaire — écrites à
     * l'époque où il était le seul espace derrière une connexion. Depuis qu'un
     * voyageur a un compte, c'était faux dans les deux sens : un voyageur
     * connecté qui rouvrait `/inscription` atterrissait sur l'écran de
     * connexion **propriétaire**, où on lui demandait un numéro de téléphone
     * qu'il n'a jamais donné. Constaté au clic, pas en lisant le code.
     */
    public function test_les_redirections_ne_renvoient_pas_le_voyageur_chez_le_proprietaire(): void
    {
        // Connecté, il retourne à ses séjours — pas dans l'espace propriétaire.
        $this->post('/inscription', $this->voyageur());
        $this->post('/inscription/code', ['code' => self::$dernier]);

        $this->get('/inscription')->assertRedirect('/mes-reservations');

        // Déconnecté, il est renvoyé sur **sa** porte, pas sur celle du
        // propriétaire.
        $this->post('/deconnexion');
        $this->get('/mes-reservations')->assertRedirect('/connexion/client');

        // Et le propriétaire garde la sienne.
        $this->get('/proprietaire')->assertRedirect('/proprietaire/connexion');
    }

    // ── Propriétaire ─────────────────────────────────────────────────

    public function test_un_proprietaire_s_inscrit_en_trois_temps(): void
    {
        $this->post('/proprietaire/inscription', $this->proprietaire())
            ->assertRedirect('/proprietaire/inscription/code');

        $this->assertSame(0, Owner::count());

        // Le code valide l'adresse et mène à la fiche — il ne crée rien.
        $this->post('/proprietaire/inscription/code', ['code' => self::$dernier])
            ->assertRedirect('/proprietaire/inscription/fiche');

        $this->assertSame(0, Owner::count());

        $this->post('/proprietaire/inscription/fiche', $this->fiche())
            ->assertRedirect('/proprietaire/logements/nouveau');

        $owner = Owner::query()->firstOrFail();
        $this->assertSame('nouvelle-proprio@example.com', $owner->email);
        $this->assertNotNull($owner->email_verified_at);
        // Le numéro est rangé en E.164 dès la session.
        $this->assertSame('+261341234567', $owner->phone);
        $this->assertFalse($owner->is_demo);
        $this->assertAuthenticatedAs($owner, 'proprietaire');
    }

    /**
     * **Le test qui protège l'échelle de confiance.** S'inscrire ne publie
     * rien : le compte naît sans logement, et le premier qu'il créera partira
     * en brouillon au niveau 1.
     */
    public function test_s_inscrire_ne_publie_rien(): void
    {
        $this->inscrireProprietaire();

        $owner = Owner::query()->firstOrFail();

        $this->assertCount(0, $owner->listings);
        // La clé d'accès est posée : c'est la récupération du mot de passe.
        $this->assertNotEmpty($owner->access_key);
        // Le numéro n'est pas encore prouvé : c'est l'e-mail qui l'est.
        $this->assertNull($owner->phone_verified_at);
        // Et il n'y a plus de mot de passe : la connexion se fait par code.
        $this->assertNull($owner->password);
    }

    public function test_un_numero_fixe_est_refuse_sur_la_fiche(): void
    {
        // 020 est une ligne fixe : WhatsApp n'y arrive pas, et c'est par là
        // que Vayla rappelle.
        $this->post('/proprietaire/inscription', $this->proprietaire());
        $this->post('/proprietaire/inscription/code', ['code' => self::$dernier]);

        $this->post('/proprietaire/inscription/fiche', $this->fiche(['phone' => '020 22 000 01']))
            ->assertSessionHasErrors('phone');

        $this->assertSame(0, Owner::count());
    }

    public function test_un_numero_deja_pris_est_refuse(): void
    {
        $this->seed();

        $this->post('/proprietaire/inscription', $this->proprietaire());
        $this->post('/proprietaire/inscription/code', ['code' => self::$dernier]);

        $this->post('/proprietaire/inscription/fiche', $this->fiche(['phone' => '+261 34 00 000 01']))
            ->assertSessionHasErrors('phone');
    }

    /**
     * **La fiche n'est atteignable qu'avec une adresse vérifiée.** Sans ce
     * garde, on créerait un compte propriétaire en postant directement, sans
     * qu'aucune adresse n'ait été prouvée.
     */
    public function test_la_fiche_refuse_une_adresse_non_verifiee(): void
    {
        $this->get('/proprietaire/inscription/fiche')->assertRedirect('/proprietaire/inscription');

        $this->post('/proprietaire/inscription/fiche', $this->fiche())
            ->assertRedirect('/proprietaire/inscription');

        $this->assertSame(0, Owner::count());
    }

    public function test_le_code_est_envoye_a_l_adresse_saisie(): void
    {
        $this->post('/proprietaire/inscription', $this->proprietaire());

        $ligne = VerificationCode::query()->latest('id')->firstOrFail();

        $this->assertSame('nouvelle-proprio@example.com', $ligne->destination);
        $this->assertSame(VerificationKind::Email, $ligne->kind);
        // Le code n'est jamais en clair en base.
        $this->assertTrue(Hash::check(self::$dernier, $ligne->code_hash));
    }

    /** Un compte ouvert ne repasse pas par l'inscription. */
    public function test_un_connecte_ne_voit_pas_l_inscription(): void
    {
        $this->seed();
        $owner = Owner::query()->firstOrFail();

        $this->actingAs($owner, 'proprietaire')->get('/proprietaire/inscription')->assertRedirect();
    }

    /** Les boutons « Publier un logement » mènent enfin quelque part. */
    public function test_la_page_d_inscription_proprietaire_s_ouvre(): void
    {
        $this->get('/proprietaire/inscription')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Owner/Register'));
    }
}
