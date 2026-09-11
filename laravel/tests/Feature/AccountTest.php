<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\Photo;
use App\Models\User;
use App\Services\OwnerPortraitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

/**
 * Les deux écrans « Mes informations ».
 *
 * **Rien n'était modifiable avant.** Le nom, le numéro WhatsApp, la ville et
 * le compte mobile money étaient saisis à l'inscription puis figés : un
 * propriétaire qui changeait de numéro recevait ses demandes sur une ligne
 * qu'il n'avait plus — au pire moment, puisqu'une demande expire en 48 h.
 *
 * Ce que ces tests protègent :
 *
 * 1. **L'adresse e-mail ne se change pas depuis une session ouverte.** Elle
 *    est l'identifiant de connexion depuis la disparition des mots de passe :
 *    la rendre modifiable reviendrait à offrir le compte à qui a emprunté le
 *    téléphone. Côté voyageur, elle rattache en plus les séjours au compte.
 * 2. **Changer de numéro annule sa confirmation.** `phone_verified_at`
 *    n'enregistre pas une vérification maison : il enregistre le fait que le
 *    lien d'accès envoyé sur ce WhatsApp a servi. Le numéro changé, cette
 *    preuve ne porte plus sur rien — et une donnée qui ment sur une
 *    vérification est exactement ce que Vayla reproche aux annonces.
 * 3. **On ne prend pas le numéro d'un confrère**, mais on garde le sien : sans
 *    `ignore`, enregistrer sans toucher au numéro échouerait sur « déjà pris »
 *    — par soi-même.
 */
class AccountTest extends TestCase
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

    /** @return array<string, mixed> */
    private function fiche(Owner $owner, array $change = []): array
    {
        return array_merge([
            'name' => $owner->name,
            'phone' => $owner->phone,
            'city' => $owner->city,
            'mobile_money' => $owner->mobile_money,
            'mobile_money_operator' => $owner->mobile_money_operator,
        ], $change);
    }

    public function test_le_proprietaire_ouvre_ses_informations(): void
    {
        $owner = $this->hanta();

        $this->actingAs($owner, 'proprietaire')
            ->get('/proprietaire/compte')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Owner/Account')
                ->where('compte.name', $owner->name)
                ->where('compte.email', $owner->email)
                ->has('operateurs')
            );
    }

    public function test_le_proprietaire_corrige_sa_fiche(): void
    {
        $owner = $this->hanta();

        $this->actingAs($owner, 'proprietaire')
            ->post('/proprietaire/compte', $this->fiche($owner, [
                'name' => 'Hanta Rakotomalala',
                'city' => 'Nosy Be',
            ]))
            ->assertRedirect()
            ->assertSessionHas('succes');

        $owner->refresh();

        $this->assertSame('Hanta Rakotomalala', $owner->name);
        $this->assertSame('Nosy Be', $owner->city);
    }

    /**
     * **Enregistrer sans toucher au numéro doit passer.** C'est le cas
     * majoritaire — on vient corriger son nom — et une règle `unique` sans
     * `ignore` le refuserait en accusant le compte de se voler lui-même.
     */
    public function test_garder_son_propre_numero_n_est_pas_un_doublon(): void
    {
        $owner = $this->hanta();

        $this->actingAs($owner, 'proprietaire')
            ->post('/proprietaire/compte', $this->fiche($owner, ['name' => 'Hanta R.']))
            ->assertSessionHasNoErrors();
    }

    public function test_on_ne_prend_pas_le_numero_d_un_confrere(): void
    {
        $owner = $this->hanta();
        $autre = Owner::query()->where('id', '!=', $owner->id)->whereNotNull('phone')->firstOrFail();

        $this->actingAs($owner, 'proprietaire')
            ->post('/proprietaire/compte', $this->fiche($owner, ['phone' => $autre->phone]))
            ->assertSessionHasErrors('phone');

        $this->assertNotSame($autre->phone, $owner->refresh()->phone);
    }

    /**
     * **La preuve du numéro tombe avec le numéro.** Elle disait « cette
     * personne tient cette ligne » : appliquée à une autre ligne, elle
     * n'énonce plus rien de vrai.
     */
    public function test_changer_de_numero_annule_sa_confirmation(): void
    {
        $owner = $this->hanta();
        $owner->forceFill(['phone_verified_at' => Carbon::now()])->save();

        $this->actingAs($owner, 'proprietaire')
            ->post('/proprietaire/compte', $this->fiche($owner, ['phone' => '+261 34 11 111 11']))
            ->assertSessionHasNoErrors();

        $owner->refresh();

        $this->assertSame('+261341111111', $owner->phone, 'Le numéro est normalisé, jamais stocké tel qu’il est tapé.');
        $this->assertNull($owner->phone_verified_at);
    }

    /** Le même enregistrement, numéro inchangé, garde la confirmation. */
    public function test_garder_son_numero_garde_sa_confirmation(): void
    {
        $owner = $this->hanta();
        $owner->forceFill(['phone_verified_at' => Carbon::now()])->save();

        $this->actingAs($owner, 'proprietaire')
            ->post('/proprietaire/compte', $this->fiche($owner, ['name' => 'Hanta R.']))
            ->assertSessionHasNoErrors();

        $this->assertNotNull($owner->refresh()->phone_verified_at);
    }

    /**
     * **L'adresse n'a pas de champ, donc pas de porte.** Le test poste
     * quand même la clé : ce qui n'est pas dans les règles ne doit jamais
     * arriver jusqu'au modèle.
     */
    public function test_l_adresse_du_proprietaire_ne_se_change_pas_ici(): void
    {
        $owner = $this->hanta();
        $avant = $owner->email;

        $this->actingAs($owner, 'proprietaire')
            ->post('/proprietaire/compte', $this->fiche($owner, ['email' => 'pirate@example.com']))
            ->assertSessionHasNoErrors();

        $this->assertSame($avant, $owner->refresh()->email);
    }

    /**
     * **L'adresse exacte n'est jamais publiée**, et c'est ce qui rend
     * acceptable de la demander. Elle sert à la facture — qui doit désigner
     * quelqu'un pour être payable — et à la vérification, qui doit savoir où
     * aller.
     */
    public function test_le_proprietaire_enregistre_son_adresse_exacte(): void
    {
        $owner = $this->hanta();

        $this->actingAs($owner, 'proprietaire')
            ->post('/proprietaire/compte', $this->fiche($owner, [
                'address' => 'Lot ZZ 4242 ter Ampasanimalo',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Lot ZZ 4242 ter Ampasanimalo', $owner->refresh()->address);

        // Et elle ne sort par aucune des portes publiques du site. La chaîne
        // est volontairement inventée : « Analamahitsy » est un vrai quartier,
        // et il figure déjà dans le catalogue — le test aurait échoué pour une
        // raison qui n'a rien à voir avec l'adresse du compte.
        $this->get('/logements/villa-ambatoloaka')->assertOk()->assertDontSee('Lot ZZ 4242');
        $this->get('/')->assertOk()->assertDontSee('Lot ZZ 4242');
    }

    /**
     * **Le portrait ne passe pas par la table `photos`.**
     *
     * Celle-ci porte les photographies de Commons et celles des logements :
     * crédit obligatoire, trois résolutions, et `PhotoFilesTest` la surveille
     * fichier par fichier. Un visage y entrerait comme un orphelin sans
     * auteur — et ferait échouer une suite qui n'a rien à voir.
     */
    public function test_le_portrait_se_pose_et_se_retire(): void
    {
        $owner = $this->hanta();
        $service = app(OwnerPortraitService::class);

        $cle = $service->poser($owner, $this->image(600));

        $this->assertSame($cle, $owner->refresh()->portrait);
        $this->assertFileExists($service->dossier()."/{$cle}-160.webp");
        $this->assertFileExists($service->dossier()."/{$cle}-480.webp");
        $this->assertDatabaseCount('photos', Photo::query()->count());

        // Retirer efface le compte **et le disque** : un visage qui traîne
        // n'est pas un octet comme un autre.
        $service->retirer($owner);

        $this->assertNull($owner->refresh()->portrait);
        $this->assertFileDoesNotExist($service->dossier()."/{$cle}-160.webp");
    }

    /**
     * **Le remplacement efface l'ancien fichier** : sans ça, chaque nouvelle
     * photo laisserait le visage précédent sur le disque, que plus rien ne
     * nomme.
     */
    public function test_changer_de_portrait_efface_le_precedent(): void
    {
        $owner = $this->hanta();
        $service = app(OwnerPortraitService::class);

        $premier = $service->poser($owner, $this->image(600));
        $second = $service->poser($owner->refresh(), $this->image(600));

        $this->assertNotSame($premier, $second);
        $this->assertFileDoesNotExist($service->dossier()."/{$premier}-160.webp");
        $this->assertFileExists($service->dossier()."/{$second}-160.webp");

        $service->retirer($owner->refresh());
    }

    /**
     * **Une image minuscule est refusée avec sa taille**, pas par un
     * « fichier invalide » qui laisse chercher. Le seuil est bas — 200 px —
     * parce qu'un portrait s'affiche à 2 rem : refuser l'unique photo que
     * quelqu'un a de lui-même serait refuser le portrait tout court.
     */
    public function test_un_portrait_minuscule_est_refuse_avec_sa_taille(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/120 pixels/');

        app(OwnerPortraitService::class)->poser($this->hanta(), $this->image(120));
    }

    /** Une image carrée de la taille demandée, écrite dans un fichier temporaire. */
    private function image(int $cote): UploadedFile
    {
        $image = imagecreatetruecolor($cote, $cote);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 120, 90));

        $chemin = tempnam(sys_get_temp_dir(), 'portrait').'.jpg';
        imagejpeg($image, $chemin);
        imagedestroy($image);

        return new UploadedFile($chemin, 'portrait.jpg', 'image/jpeg', null, true);
    }

    /**
     * **Nom, prénom et téléphone — trois champs, et chacun sert deux fois.**
     * Ils disent au propriétaire qui arrive et par où le rappeler, et ils
     * pré-remplissent la demande de séjour. Sans ce second usage, ce serait un
     * dossier de plus à constituer, et Vayla n'en constitue pas.
     */
    public function test_le_voyageur_corrige_ses_informations(): void
    {
        $user = User::create(['email' => 'compte@example.com']);

        $this->actingAs($user)
            ->get('/mon-compte')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Account')
                ->where('compte.email', 'compte@example.com')
            );

        $this->actingAs($user)
            ->post('/mon-compte', [
                'first_name' => 'Jean',
                'last_name' => 'Rakotobe',
                'phone' => '+261 34 00 000 77',
            ])
            ->assertRedirect()
            ->assertSessionHas('succes');

        $user->refresh();

        $this->assertSame('Jean', $user->first_name);
        $this->assertSame('Rakotobe', $user->last_name);
        $this->assertSame('+261 34 00 000 77', $user->phone);
    }

    /**
     * **Le nom complet est dérivé, jamais stocké.** Deux endroits où vit le
     * même fait finissent toujours par diverger — c'est la règle de `perks`,
     * appliquée à l'identité.
     */
    public function test_le_nom_complet_se_deduit_des_deux_champs(): void
    {
        $user = User::create([
            'email' => 'compose@example.com',
            'first_name' => 'Jean',
            'last_name' => 'Rakotobe',
        ]);

        $this->assertSame('Jean Rakotobe', $user->name);

        // Et un nom entier — celui que renvoie un fournisseur social — se
        // sépare tout seul : les appelants n'ont pas à savoir le faire.
        $autre = User::create(['email' => 'social@example.com', 'name' => 'Claire Fontaine']);

        $this->assertSame('Claire', $autre->first_name);
        $this->assertSame('Fontaine', $autre->last_name);
    }

    /** Tout effacer est un droit : le formulaire de séjour redemandera. */
    public function test_le_voyageur_peut_tout_effacer(): void
    {
        $user = User::create([
            'email' => 'compte2@example.com',
            'first_name' => 'Jean',
            'last_name' => 'Rakotobe',
            'phone' => '+261340000077',
        ]);

        $this->actingAs($user)
            ->post('/mon-compte', ['first_name' => '', 'last_name' => '', 'phone' => ''])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertNull($user->first_name);
        $this->assertNull($user->name);
        $this->assertNull($user->phone);
    }

    public function test_l_adresse_du_voyageur_ne_se_change_pas_ici(): void
    {
        $user = User::create(['email' => 'compte3@example.com']);

        $this->actingAs($user)
            ->post('/mon-compte', ['first_name' => 'Jean', 'email' => 'pirate@example.com'])
            ->assertSessionHasNoErrors();

        $this->assertSame('compte3@example.com', $user->refresh()->email);
    }

    /**
     * **Le compte pré-remplit la demande de séjour, il ne la conditionne
     * pas.** C'est la seule raison pour laquelle Vayla garde un nom et un
     * numéro ; et un visiteur sans compte doit continuer d'arriver sur un
     * formulaire vide, jamais sur une invitation à s'inscrire.
     */
    public function test_le_compte_pre_remplit_la_demande_de_sejour(): void
    {
        $user = User::create([
            'email' => 'prefill@example.com',
            'first_name' => 'Jean',
            'last_name' => 'Rakotobe',
            'phone' => '+261340000077',
        ]);

        $this->actingAs($user)
            ->get('/logements/villa-ambatoloaka/reserver')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('voyageur.traveller', 'Jean Rakotobe')
                ->where('voyageur.traveller_phone', '+261340000077')
                ->where('voyageur.traveller_email', 'prefill@example.com')
            );

    }

    /**
     * **Et un visiteur sans compte arrive sur un formulaire vide**, jamais sur
     * une invitation à s'inscrire : demander un séjour reste possible sans
     * compte, c'est la promesse du produit.
     *
     * Le test est à part parce qu'`actingAs` vaut pour toute la méthode : une
     * seconde requête dans le même test serait restée connectée, et
     * l'assertion aurait passé pour de mauvaises raisons.
     */
    public function test_un_visiteur_arrive_sur_un_formulaire_vide(): void
    {
        $this->get('/logements/villa-ambatoloaka/reserver')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('voyageur', null));
    }

    public function test_les_comptes_sont_fermes_aux_visiteurs(): void
    {
        $this->get('/mon-compte')->assertRedirect('/connexion/client');
        $this->get('/proprietaire/compte')->assertRedirect('/proprietaire/connexion');
    }

    /**
     * **Aucun mot de passe ne revient par cette porte.** L'espace propriétaire
     * n'en a plus : un champ réintroduit par une refonte se verrait ici.
     */
    public function test_aucun_mot_de_passe_sur_l_ecran_du_compte(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Owner/Account.vue'));

        preg_match('/<template>(.*)<\/template>/s', $source, $balisage);

        $this->assertNotEmpty($balisage);
        $this->assertStringNotContainsString('password', $balisage[1]);
    }
}
