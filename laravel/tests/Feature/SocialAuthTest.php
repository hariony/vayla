<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * La connexion par Google, Facebook et Apple.
 *
 * **Aucun appel réseau.** Socialite est simulé de bout en bout : un test qui
 * dépendrait vraiment de Google échouerait un jour de panne chez eux, et
 * n'aurait rien prouvé sur notre code.
 *
 * Ce que ces tests protègent, dans l'ordre d'importance :
 *
 * 1. **Une adresse non garantie ne rattache jamais.** C'est le seul endroit du
 *    dispositif où une erreur ouvre le compte de quelqu'un d'autre : il
 *    suffirait d'ouvrir un compte Facebook avec l'adresse d'un tiers.
 * 2. **Aucun doublon silencieux.** Ni deux comptes pour une même personne, ni
 *    deux identités pour un même couple `(provider, provider_user_id)`.
 * 3. **La session est régénérée** après connexion : sans ça, l'identifiant de
 *    session obtenu avant l'authentification reste valable — c'est la
 *    fixation de session.
 */
class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Sans identifiants, le contrôleur répond 404 : c'est voulu, et il
        // faut donc les poser pour que les fournisseurs « existent ».
        config([
            'services.google.client_id' => 'test-google',
            'services.facebook.client_id' => 'test-facebook',
            'services.apple.client_id' => 'test-apple',
        ]);

        // **La façade est branchée une seule fois**, et distribue les pilotes
        // enregistrés au fil du test. Rebrancher `Socialite::shouldReceive()`
        // en cours de route recrée un mock — et réinitialiser le conteneur
        // Mockery entre deux appels le casse net : c'est ce qui faisait tomber
        // le test multi-fournisseurs sur une erreur fatale.
        Socialite::shouldReceive('driver')->andReturnUsing(
            fn (string $nom) => $this->pilotes[$nom] ?? throw new \RuntimeException("Fournisseur non simulé : {$nom}")
        );
    }

    /** @var array<string, mixed> les pilotes simulés, par fournisseur */
    private array $pilotes = [];

    /**
     * Une identité telle que Socialite la rend.
     *
     * `$brut` est la charge utile du fournisseur — c'est **là** que vit
     * `email_verified`, et nulle part ailleurs.
     */
    private function identite(string $id, ?string $email, ?string $nom = null, array $brut = []): SocialiteUser
    {
        $user = new SocialiteUser;
        $user->id = $id;
        $user->email = $email;
        $user->name = $nom;
        $user->avatar = null;
        $user->user = $brut;

        return $user;
    }

    private function simuler(string $provider, SocialiteUser $identite): void
    {
        $pilote = Mockery::mock(Provider::class);
        $pilote->shouldReceive('user')->andReturn($identite);
        $pilote->shouldReceive('redirect')->andReturn(redirect('https://exemple.test/oauth'));

        $this->pilotes[$provider] = $pilote;
    }

    /** Le fournisseur a répondu, mais l'échange a échoué de notre côté. */
    private function simulerEchec(string $provider): void
    {
        $pilote = Mockery::mock(Provider::class);
        $pilote->shouldReceive('user')->andThrow(new \RuntimeException('invalid state'));

        $this->pilotes[$provider] = $pilote;
    }

    // ── Cas 1 : un nouvel utilisateur, par fournisseur ───────────────

    public static function fournisseurs(): array
    {
        return [
            'google' => ['google', ['email_verified' => true]],
            'facebook' => ['facebook', []],
            'apple' => ['apple', ['email_verified' => true]],
        ];
    }

    #[DataProvider('fournisseurs')]
    public function test_un_nouvel_utilisateur_ouvre_un_compte(string $provider, array $brut): void
    {
        $this->simuler($provider, $this->identite('p-1', 'neuf@example.com', 'Claire Fontaine', $brut));

        $this->get("/auth/{$provider}/callback")->assertRedirect('/mes-reservations');

        $user = User::query()->firstOrFail();
        $this->assertSame('neuf@example.com', $user->email);
        $this->assertSame('Claire Fontaine', $user->name);
        $this->assertAuthenticatedAs($user);

        $compte = SocialAccount::query()->firstOrFail();
        $this->assertSame($provider, $compte->provider);
        $this->assertSame('p-1', $compte->provider_user_id);
        $this->assertSame($user->id, $compte->compte_id);
        $this->assertSame(User::class, $compte->compte_type);
    }

    /**
     * **L'adresse n'est marquée vérifiée que si le fournisseur l'atteste.**
     * Facebook ne l'atteste pas : lui prêter notre preuve — celle du code —
     * reviendrait à inventer une vérification.
     */
    public function test_facebook_ne_marque_pas_l_adresse_verifiee(): void
    {
        $this->simuler('facebook', $this->identite('fb-1', 'neuf@example.com'));
        $this->get('/auth/facebook/callback');

        $this->assertNull(User::query()->firstOrFail()->email_verified_at);
        $this->assertFalse(SocialAccount::query()->firstOrFail()->email_verified);
    }

    public function test_google_marque_l_adresse_verifiee(): void
    {
        $this->simuler('google', $this->identite('g-1', 'neuf@example.com', null, ['email_verified' => true]));
        $this->get('/auth/google/callback');

        $this->assertNotNull(User::query()->firstOrFail()->email_verified_at);
    }

    // ── Cas 2 : l'identité est déjà connue ───────────────────────────

    public function test_une_identite_connue_connecte_sans_rien_creer(): void
    {
        $user = User::create(['email' => 'connu@example.com', 'name' => 'Déjà là']);
        $user->socialAccounts()->create([
            'provider' => 'google',
            'provider_user_id' => 'g-42',
            'email' => 'connu@example.com',
            'email_verified' => true,
        ]);

        $this->simuler('google', $this->identite('g-42', 'connu@example.com', 'Nouveau Nom', ['email_verified' => true]));
        $this->get('/auth/google/callback')->assertRedirect('/mes-reservations');

        $this->assertSame(1, User::count());
        $this->assertSame(1, SocialAccount::count());
        $this->assertAuthenticatedAs($user->fresh());

        // Les champs non critiques suivent ; l'adresse du compte, non.
        $this->assertSame('Nouveau Nom', SocialAccount::query()->firstOrFail()->name);
        $this->assertSame('connu@example.com', $user->fresh()->email);
    }

    /**
     * **Apple ne transmet le nom qu'à la première autorisation.** L'écraser
     * avec du vide à la deuxième connexion effacerait ce que la première avait
     * appris.
     */
    public function test_un_nom_absent_n_efface_pas_celui_qu_on_a_deja(): void
    {
        $user = User::create(['email' => 'apple@example.com', 'name' => 'Hanta R.']);
        $user->socialAccounts()->create([
            'provider' => 'apple',
            'provider_user_id' => 'a-1',
            'name' => 'Hanta R.',
            'email_verified' => true,
        ]);

        $this->simuler('apple', $this->identite('a-1', 'apple@example.com', null, ['email_verified' => true]));
        $this->get('/auth/apple/callback');

        $this->assertSame('Hanta R.', SocialAccount::query()->firstOrFail()->name);
        $this->assertSame('Hanta R.', $user->fresh()->name);
    }

    // ── Cas 3 : une adresse désigne un compte existant ───────────────

    /** Adresse **garantie** : le rattachement est légitime. */
    public function test_une_adresse_garantie_rattache_au_compte_existant(): void
    {
        $user = User::create(['email' => 'claire@example.com']);

        $this->simuler('google', $this->identite('g-9', 'claire@example.com', null, ['email_verified' => true]));
        $this->get('/auth/google/callback')->assertRedirect('/mes-reservations');

        $this->assertSame(1, User::count());
        $this->assertSame($user->id, SocialAccount::query()->firstOrFail()->compte_id);
        $this->assertAuthenticatedAs($user->fresh());
    }

    /**
     * **Le test qui protège les comptes des autres.**
     *
     * Facebook ne garantit pas l'adresse. Sans ce refus, ouvrir un compte
     * Facebook avec l'adresse de quelqu'un suffirait à entrer chez lui.
     */
    public function test_une_adresse_non_garantie_ne_rattache_pas(): void
    {
        User::create(['email' => 'claire@example.com']);

        $this->simuler('facebook', $this->identite('fb-9', 'claire@example.com'));

        $this->get('/auth/facebook/callback')
            ->assertRedirect('/connexion/client')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(1, User::count());
        $this->assertSame(0, SocialAccount::count());
    }

    // ── Cas 4 : plusieurs fournisseurs, un seul compte ───────────────

    public function test_un_meme_compte_porte_plusieurs_fournisseurs(): void
    {
        $this->simuler('google', $this->identite('g-1', 'multi@example.com', null, ['email_verified' => true]));
        $this->get('/auth/google/callback');
        $this->post('/deconnexion');

        $this->simuler('apple', $this->identite('a-1', 'multi@example.com', null, ['email_verified' => true]));
        $this->get('/auth/apple/callback');

        $this->assertSame(1, User::count());
        $this->assertSame(2, SocialAccount::count());
        $this->assertEqualsCanonicalizing(
            ['google', 'apple'],
            SocialAccount::query()->pluck('provider')->all()
        );
    }

    // ── Les bords ────────────────────────────────────────────────────

    /**
     * **Sans adresse, le compte s'ouvre quand même.** Facebook n'en donne pas
     * quand le compte a été ouvert par téléphone. Le compte est diminué — il
     * ne rattachera aucune réservation — mais refuser afficherait un bouton
     * qui échoue sans que personne ne comprenne.
     */
    public function test_un_utilisateur_sans_adresse_ouvre_quand_meme_un_compte(): void
    {
        $this->simuler('facebook', $this->identite('fb-nomail', null));

        $this->get('/auth/facebook/callback')->assertRedirect('/mes-reservations');

        $user = User::query()->firstOrFail();
        $this->assertNull($user->email);
        $this->assertAuthenticatedAs($user);
    }

    /** Deux comptes sans adresse ne se confondent pas : l'unique tient sur NULL. */
    public function test_deux_comptes_sans_adresse_restent_distincts(): void
    {
        $this->simuler('facebook', $this->identite('fb-a', null));
        $this->get('/auth/facebook/callback');
        $this->post('/deconnexion');

        $this->simuler('facebook', $this->identite('fb-b', null));
        $this->get('/auth/facebook/callback');

        $this->assertSame(2, User::count());
        $this->assertSame(2, SocialAccount::count());
    }

    /**
     * **Un propriétaire inconnu ne se crée pas au retour du fournisseur.**
     *
     * `owners.phone` est obligatoire — c'est par là que Vayla appelle pour la
     * vérification — et aucun fournisseur ne le donne. Le compte naît donc sur
     * la fiche, et l'identité sociale s'y lie une fois le numéro connu.
     */
    public function test_un_proprietaire_inconnu_passe_par_la_fiche(): void
    {
        $this->simuler('google', $this->identite('g-pro', 'proprio@example.com', 'Hanta R.', ['email_verified' => true]));

        $this->get('/proprietaire/auth/google');
        $this->get('/auth/google/callback')->assertRedirect('/proprietaire/inscription/fiche');

        // Rien en base tant que la fiche n'est pas remplie.
        $this->assertSame(0, Owner::count());
        $this->assertSame(0, SocialAccount::count());
        $this->assertGuest('proprietaire');

        $this->post('/proprietaire/inscription/fiche', ['name' => 'Hanta R.', 'phone' => '034 12 345 67'])
            ->assertRedirect('/proprietaire/logements/nouveau');

        $owner = Owner::query()->firstOrFail();
        $this->assertSame('proprio@example.com', $owner->email);
        $this->assertSame('+261341234567', $owner->phone);
        $this->assertAuthenticatedAs($owner, 'proprietaire');

        $lien = SocialAccount::query()->firstOrFail();
        $this->assertSame(Owner::class, $lien->compte_type);
        $this->assertSame($owner->id, $lien->compte_id);
    }

    /** Un propriétaire déjà connu entre directement dans son espace. */
    public function test_un_proprietaire_connu_entre_directement(): void
    {
        $this->seed();
        $hanta = Owner::query()->where('name', 'like', 'Hanta%')->firstOrFail();

        $this->simuler('google', $this->identite('g-h', $hanta->email, null, ['email_verified' => true]));

        $this->get('/proprietaire/auth/google');
        $this->get('/auth/google/callback')->assertRedirect('/proprietaire');

        $this->assertAuthenticatedAs($hanta, 'proprietaire');
        $this->assertSame(Owner::class, SocialAccount::query()->firstOrFail()->compte_type);
    }

    /**
     * **La même personne peut être voyageuse et propriétaire**, avec le même
     * compte Google. C'est le sens des deux gardes, et la clé unique porte donc
     * le type du compte.
     */
    public function test_un_meme_compte_fournisseur_sert_les_deux_espaces(): void
    {
        $this->simuler('google', $this->identite('g-duo', 'duo@example.com', null, ['email_verified' => true]));

        $this->get('/auth/google/callback')->assertRedirect('/mes-reservations');
        $this->post('/deconnexion');

        $this->get('/proprietaire/auth/google');
        $this->get('/auth/google/callback')->assertRedirect('/proprietaire/inscription/fiche');
        $this->post('/proprietaire/inscription/fiche', ['name' => 'Duo Test', 'phone' => '034 99 999 99']);

        $this->assertSame(1, User::count());
        $this->assertSame(1, Owner::count());
        $this->assertSame(2, SocialAccount::count());
    }

    /** Sans marqueur d'espace, on retombe sur le voyageur : la porte publique. */
    public function test_sans_marqueur_le_retour_mene_a_l_espace_voyageur(): void
    {
        $this->simuler('google', $this->identite('g-nomark', 'nomark@example.com', null, ['email_verified' => true]));

        $this->get('/auth/google/callback')->assertRedirect('/mes-reservations');

        $this->assertSame(0, Owner::count());
    }

    /**
     * **L'invite Google n'apparaît que sur les portes du voyageur.**
     *
     * Elle connecte en un clic, et sur la garde `web`. Deux endroits où c'est
     * faux :
     *
     * — **l'aiguillage `/connexion`**, dont la page entière sert à demander
     *   « client ou propriétaire ? ». Y répondre à la place de l'utilisateur
     *   vide la page de son objet.
     * — **les écrans du propriétaire**, où elle ouvrirait carrément la
     *   mauvaise garde : `oneTap` connecte un `User`, jamais un `Owner`.
     *
     * Elle était montée par `AccessShell`, donc partout. Le test regarde le
     * balisage parce que c'est la seule façon de voir revenir l'erreur à la
     * prochaine refonte du cadre.
     */
    public function test_l_invite_google_ne_sert_que_les_portes_du_voyageur(): void
    {
        $porte = ['Auth/Register.vue', 'Access/Client.vue'];
        $interdit = ['Access/Index.vue', 'Owner/Login.vue', 'Owner/Register.vue', 'Owner/Profile.vue', 'Auth/Code.vue'];

        foreach ($porte as $ecran) {
            $this->assertStringContainsString(
                '<GoogleOneTap />',
                file_get_contents(resource_path('js/Pages/'.$ecran)),
                $ecran.' est une porte voyageur : elle porte l\'invite.'
            );
        }

        foreach ($interdit as $ecran) {
            $this->assertStringNotContainsString(
                'GoogleOneTap',
                file_get_contents(resource_path('js/Pages/'.$ecran)),
                $ecran.' ne doit pas trancher à la place de l\'utilisateur.'
            );
        }

        // Et surtout pas le cadre commun, qui sert les cinq écrans.
        $this->assertStringNotContainsString(
            'GoogleOneTap',
            file_get_contents(resource_path('js/Components/AccessShell.vue'))
        );
    }

    /**
     * **Le bloc social change de forme avec le nombre de fournisseurs.**
     *
     * Seul, une cellule isolée dans un contrôle segmenté annonce un choix qui
     * n'existe pas — on cherche les autres — et à un tiers de largeur elle se
     * rate au doigt. Le libellé complet revient alors : dans la barre, le nom
     * suffit parce que le sur-titre porte le verbe ; isolé, « Google » ne dit
     * plus ce que le clic fait.
     *
     * « Continuer » et non « Se connecter » : la porte est unique, la même
     * page inscrit et connecte. Les deux autres verbes mentiraient à la
     * moitié des gens.
     */
    public function test_le_bouton_social_dit_ce_que_le_clic_fait(): void
    {
        $source = file_get_contents(resource_path('js/Components/SocialButtons.vue'));

        preg_match('/<template>(.*)<\/template>/s', $source, $balisage);

        $this->assertNotEmpty($balisage);
        $this->assertStringContainsString('Continuer avec', $balisage[1]);
        $this->assertStringContainsString("'is-seul': seul", $balisage[1]);

        // Ni « Se connecter » ni « S'inscrire » : ils mentent à la moitié des
        // visiteurs, puisque la même porte fait les deux.
        $this->assertStringNotContainsString('Se connecter avec', $balisage[1]);
        $this->assertStringNotContainsString('S’inscrire avec', $balisage[1]);
    }

    public function test_un_fournisseur_inconnu_n_existe_pas(): void
    {
        $this->get('/auth/myspace')->assertNotFound();
        $this->get('/auth/myspace/callback')->assertNotFound();
    }

    /** Un fournisseur sans identifiants n'existe pas non plus. */
    public function test_un_fournisseur_non_configure_n_existe_pas(): void
    {
        config(['services.apple.client_id' => null]);

        $this->get('/auth/apple')->assertNotFound();
    }

    /** L'utilisateur a refusé : ce n'est pas une panne. */
    public function test_un_refus_du_fournisseur_revient_avec_un_message(): void
    {
        $this->get('/auth/google/callback?error=access_denied')
            ->assertRedirect('/connexion/client')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    /** Une erreur OAuth ne dit rien de précis, et ne crée rien. */
    public function test_une_erreur_oauth_reste_generique(): void
    {
        $this->simulerEchec('google');

        $this->get('/auth/google/callback')
            ->assertRedirect('/connexion/client')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(0, User::count());
        $this->assertStringNotContainsString('invalid state', session('errors')->first('email'));
    }

    /** **Anti-fixation** : l'identifiant de session change à la connexion. */
    public function test_la_session_est_regeneree(): void
    {
        $this->startSession();
        $avant = session()->getId();

        $this->simuler('google', $this->identite('g-1', 'neuf@example.com', null, ['email_verified' => true]));
        $this->get('/auth/google/callback');

        $this->assertNotSame($avant, session()->getId());
    }

    /** Un connecté ne repasse pas par la porte : `guest` la ferme. */
    public function test_un_connecte_ne_repasse_pas_par_la_porte(): void
    {
        $this->actingAs(User::create(['email' => 'deja@example.com']));

        $this->get('/auth/google')->assertRedirect('/mes-reservations');
    }

    /** Les routes d'authentification sont bornées en débit. */
    public function test_le_depart_est_limite_en_debit(): void
    {
        $this->simuler('google', $this->identite('g-1', 'x@example.com'));
        $bloque = false;

        for ($i = 0; $i < 20; $i++) {
            if ($this->get('/auth/google')->status() === 429) {
                $bloque = true;
                break;
            }
        }

        $this->assertTrue($bloque, 'La porte sociale se laisse marteler sans limite.');
    }
}
