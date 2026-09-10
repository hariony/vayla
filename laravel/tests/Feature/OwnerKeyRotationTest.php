<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Services\OwnerKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La rotation des clés d'accès.
 *
 * C'est la contrepartie du choix « une clé, pas un mot de passe » : sans
 * moyen de révoquer, un lien qui fuite reste valable pour toujours — et un
 * lien fuite, c'est même le cas le plus banal (téléphone perdu, gérant qui
 * s'en va, message transféré).
 *
 * Ce que ces tests tiennent :
 *
 * 1. **L'ancienne clé meurt à l'instant.** Un délai de grâce laisserait aussi
 *    entrer celui qui a récupéré le lien — c'est-à-dire précisément la
 *    personne contre qui on tourne la clé.
 * 2. **Le numéro se retrouve tel qu'il est écrit.** Exiger la ponctuation
 *    exacte ferait échouer la commande au moment où on en a besoin.
 * 3. **La rotation ne touche que le propriétaire visé** — les autres liens
 *    déjà envoyés continuent de fonctionner.
 */
class OwnerKeyRotationTest extends TestCase
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

    /**
     * **Le test qui compte.** Sans lui, la révocation n'en est pas une.
     *
     * Le lien n'ouvre plus l'espace mais le **compte** : l'ancien doit
     * renvoyer sur la connexion avec un message, le nouveau doit connecter.
     */
    public function test_l_ancien_lien_cesse_immediatement_de_fonctionner(): void
    {
        $ancien = $this->hanta()->access_key;

        $this->get('/proprietaire/acces/'.$ancien)->assertRedirect('/proprietaire');
        $this->post('/proprietaire/deconnexion');

        app(OwnerKeyService::class)->tourner($this->hanta());

        $this->get('/proprietaire/acces/'.$ancien)
            ->assertRedirect('/proprietaire/connexion')
            ->assertSessionHasErrors('email');

        $this->get('/proprietaire/acces/'.$this->hanta()->fresh()->access_key)
            ->assertRedirect('/proprietaire');
    }

    public function test_la_nouvelle_cle_est_aussi_longue_que_l_ancienne(): void
    {
        app(OwnerKeyService::class)->tourner($this->hanta());

        $nouvelle = $this->hanta()->fresh()->access_key;

        $this->assertSame(32, strlen($nouvelle));
        $this->assertMatchesRegularExpression('/^[a-z0-9]{32}$/', $nouvelle);
    }

    /** La date de pose répond à « depuis combien de temps ce lien circule ? ». */
    public function test_la_date_de_pose_suit_la_rotation(): void
    {
        $avant = $this->hanta()->access_key_set_at;

        $this->travel(2)->days();
        app(OwnerKeyService::class)->tourner($this->hanta());

        $apres = $this->hanta()->fresh()->access_key_set_at;

        $this->assertNotNull($apres);
        $this->assertTrue($avant === null || $apres->greaterThan($avant));
    }

    public function test_tourner_une_cle_ne_touche_pas_les_autres(): void
    {
        $voisin = Owner::query()->where('name', 'like', 'Voahangy%')->firstOrFail();
        $sonLien = $voisin->access_key;

        app(OwnerKeyService::class)->tourner($this->hanta());

        $this->assertSame($sonLien, $voisin->fresh()->access_key);
        $this->get('/proprietaire/acces/'.$sonLien)->assertRedirect();
    }

    /**
     * « +261 34 00 000 01 », « 034 00 000 01 » et « 0340000001 » désignent la
     * même personne : on compare les neuf derniers chiffres.
     */
    public function test_le_numero_se_retrouve_quelle_que_soit_son_ecriture(): void
    {
        foreach (['+261 34 00 000 01', '0340000001', '034 00 000 01', '261340000001'] as $ecriture) {
            $this->assertSame(
                $this->hanta()->id,
                app(OwnerKeyService::class)->trouver($ecriture)?->id,
                "Écriture non reconnue : {$ecriture}"
            );
        }
    }

    /**
     * **Un numéro trop court ne désigne personne, jamais n'importe qui.**
     * Une recherche par identifiant existait ici : `0001` devenait la clé
     * primaire 1, et un numéro tapé de travers tournait la clé d'un autre
     * propriétaire — en le mettant dehors. Elle a été retirée.
     */
    public function test_un_numero_court_ou_inconnu_ne_designe_personne(): void
    {
        foreach (['0001', '1', '34', '+33 6 12 34 56 78', ''] as $entree) {
            $this->assertNull(
                app(OwnerKeyService::class)->trouver($entree),
                "Cette entrée ne devrait désigner personne : « {$entree} »"
            );
        }
    }

    public function test_la_commande_tourne_la_cle_et_affiche_le_lien(): void
    {
        $ancienne = $this->hanta()->access_key;

        $this->artisan('vayla:rotate-owner-key', ['numero' => '0340000001', '--force' => true])
            ->expectsOutputToContain('Nouveau lien')
            ->assertSuccessful();

        $this->assertNotSame($ancienne, $this->hanta()->fresh()->access_key);
    }

    public function test_la_commande_refuse_un_propriétaire_inconnu(): void
    {
        $this->artisan('vayla:rotate-owner-key', ['numero' => '0999999999', '--force' => true])
            ->assertFailed();
    }

    /** Répondre « non » ne doit rien changer : c'est tout l'intérêt de demander. */
    public function test_refuser_la_confirmation_ne_change_rien(): void
    {
        $ancienne = $this->hanta()->access_key;

        $this->artisan('vayla:rotate-owner-key', ['numero' => '0340000001'])
            ->expectsConfirmation('Changer sa clé ? L’ancien lien cessera aussitôt de fonctionner.', 'no')
            ->assertSuccessful();

        $this->assertSame($ancienne, $this->hanta()->fresh()->access_key);
    }

    public function test_tourner_toutes_les_cles_les_change_toutes(): void
    {
        $avant = Owner::query()->pluck('access_key', 'id');

        $this->artisan('vayla:rotate-owner-key', ['--all' => true, '--force' => true])->assertSuccessful();

        foreach (Owner::all() as $owner) {
            $this->assertNotSame($avant[$owner->id], $owner->access_key, "Clé inchangée : {$owner->name}");
            $this->assertSame(32, strlen($owner->access_key));
        }
    }

    /** Ni la clé ni le mot de passe ne doivent apparaître dans une sérialisation. */
    public function test_la_cle_reste_hors_des_charges_utiles(): void
    {
        app(OwnerKeyService::class)->tourner($this->hanta());

        $this->assertArrayNotHasKey('access_key', $this->hanta()->fresh()->toArray());
    }
}
