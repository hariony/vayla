<?php

namespace Tests\Unit;

use App\Support\Telephone;
use PHPUnit\Framework\TestCase;

/**
 * La normalisation des numéros de téléphone.
 *
 * **C'est la clé de connexion des propriétaires**, et personne ne le retape
 * deux fois de la même façon. Ce que ces tests protègent :
 *
 * 1. **Les cinq écritures du terrain** ouvrent le même compte — internationale,
 *    `00`, zéro national, indicatif sans `+` (ce que produit un copier-coller
 *    depuis WhatsApp) et numéro nu.
 * 2. **On compare des numéros, pas des fins de chaîne.** La version précédente
 *    rapprochait les neuf derniers chiffres : deux numéros de pays différents
 *    finissant pareil auraient ouvert le même compte.
 * 3. **Les numéros étrangers passent.** Une part des propriétaires vit en
 *    France ou à La Réunion et loue à Nosy Be : refuser un `+33` écarterait
 *    exactement ceux qui ont les moyens d'équiper un logement.
 */
class TelephoneTest extends TestCase
{
    /** Toutes les écritures d'un même numéro malgache donnent la même forme. */
    public function test_les_ecritures_du_terrain_donnent_le_meme_numero(): void
    {
        $ecritures = [
            '+261 34 00 000 01',
            '+261340000001',
            '00261340000001',
            '261340000001',
            '034 00 000 01',
            '0340000001',
            '34 00 000 01',
            '  +261-34-00-000-01  ',
        ];

        foreach ($ecritures as $brut) {
            $this->assertSame(
                '+261340000001',
                Telephone::depuis($brut)?->e164(),
                "Écriture non reconnue : « {$brut} »"
            );
        }
    }

    /**
     * **La régression que la comparaison par « neuf derniers chiffres »
     * laissait passer.** Un numéro étranger finissant comme un malgache
     * ouvrait le même compte.
     */
    public function test_deux_pays_qui_finissent_pareil_sont_deux_numeros(): void
    {
        $malgache = Telephone::depuis('+261 34 00 000 01');
        $etranger = Telephone::depuis('+33 340000001');

        $this->assertNotNull($malgache);
        $this->assertNotNull($etranger);
        $this->assertFalse($malgache->equivaut($etranger));
    }

    public function test_un_numero_etranger_est_accepte_tel_quel(): void
    {
        $numero = Telephone::depuis('+33 6 12 45 78 90');

        $this->assertSame('+33612457890', $numero?->e164());
        $this->assertFalse($numero->estMalgache());
        // On ne prétend pas savoir si un numéro étranger est mobile.
        $this->assertTrue($numero->estMobile());
        $this->assertNull($numero->operateur());
    }

    public function test_une_saisie_qui_ne_designe_rien_ne_donne_rien(): void
    {
        foreach ([null, '', '   ', 'bonjour', '12', '0340', '+261 99 00 000 01'] as $brut) {
            $this->assertNull(
                Telephone::depuis($brut),
                'Cette saisie ne devrait désigner aucun numéro : '.var_export($brut, true)
            );
        }
    }

    /**
     * `+261 99 …` n'existe pas. Le laisser passer ferait échouer un envoi
     * WhatsApp sans que personne ne sache pourquoi.
     */
    public function test_un_prefixe_malgache_inconnu_est_refuse(): void
    {
        $this->assertNull(Telephone::depuis('+261990000001'));
        $this->assertNull(Telephone::depuis('+261 10 00 000 01'));
    }

    /** WhatsApp est **le** canal de Vayla : un fixe ne reçoit pas le lien d'accès. */
    public function test_un_fixe_malgache_n_est_pas_mobile(): void
    {
        $fixe = Telephone::depuis('020 22 000 01');

        $this->assertNotNull($fixe);
        $this->assertTrue($fixe->estMalgache());
        $this->assertFalse($fixe->estMobile());
        $this->assertNull($fixe->operateur());
    }

    public function test_l_operateur_se_lit_sur_le_prefixe(): void
    {
        $attendus = [
            '032 00 000 01' => 'Orange',
            '033 00 000 01' => 'Airtel',
            '034 00 000 01' => 'Telma',
            '038 00 000 01' => 'Telma',
        ];

        foreach ($attendus as $numero => $operateur) {
            $this->assertSame($operateur, Telephone::depuis($numero)?->operateur(), $numero);
        }
    }

    /** La forme lisible se dicte : c'est ainsi qu'on donne son numéro ici. */
    public function test_la_forme_lisible_suit_le_groupement_malgache(): void
    {
        $this->assertSame('+261 34 00 000 01', Telephone::depuis('0340000001')?->lisible());
        // Aucun groupement inventé pour l'étranger : un numéro mal découpé est
        // faux à l'œil de son propriétaire.
        $this->assertSame('+33612457890', Telephone::depuis('+33 6 12 45 78 90')?->lisible());
    }
}
