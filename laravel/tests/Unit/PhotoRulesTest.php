<?php

namespace Tests\Unit;

use App\Data\Photos\PhotoUsageData;
use App\Enums\PhotoLicence;
use App\Enums\PhotoProvenance;
use App\Models\Photo;
use App\Services\Photos\PhotoRemovalPolicy;
use PHPUnit\Framework\TestCase;

/**
 * Les règles de la photothèque, sans base ni conteneur : **ce qu'on peut faire
 * d'une photo se déduit de sa provenance**, et c'est testable comme une
 * fonction pure — c'est l'intérêt de les avoir sorties du service.
 */
class PhotoRulesTest extends TestCase
{
    private function photo(string $folder, string $key, bool $ia = false): Photo
    {
        return (new Photo)->forceFill(['folder' => $folder, 'key' => $key, 'is_ai' => $ia]);
    }

    public function test_la_provenance_se_lit_sur_le_dossier_et_la_cle(): void
    {
        $this->assertSame(PhotoProvenance::Commons, PhotoProvenance::de($this->photo('lieux', 'tana-rue')));
        $this->assertSame(PhotoProvenance::Equipe, PhotoProvenance::de($this->photo('destinations', 'nosy-be-abcd1234')));
        $this->assertSame(PhotoProvenance::Proprietaire, PhotoProvenance::de($this->photo('annonces', '3/abcdef')));
        $this->assertSame(PhotoProvenance::Demonstration, PhotoProvenance::de($this->photo('lieux', 'an-plage-ifaty')));
        $this->assertSame(PhotoProvenance::Generee, PhotoProvenance::de($this->photo('lieux', 'ia-chambre', true)));
    }

    /** Le tableau de la photothèque, tel qu'il est écrit dans `PhotoProvenance`. */
    public function test_chaque_provenance_a_ses_droits(): void
    {
        $droits = fn (PhotoProvenance $p) => [$p->legendeModifiable(), $p->creditModifiable(), $p->licenceModifiable()];

        $this->assertSame([true, true, false], $droits(PhotoProvenance::Commons), 'La licence de Commons est celle de l’auteur.');
        $this->assertSame([true, true, true], $droits(PhotoProvenance::Equipe));
        $this->assertSame([true, false, false], $droits(PhotoProvenance::Proprietaire), 'La légende est le texte lu aux malvoyants.');
        $this->assertSame([false, false, false], $droits(PhotoProvenance::Demonstration));
        $this->assertSame([false, false, false], $droits(PhotoProvenance::Generee));
    }

    public function test_seule_une_photo_de_l_equipe_qui_n_illustre_rien_se_supprime(): void
    {
        $politique = new PhotoRemovalPolicy;
        $galerie = [PhotoUsageData::destination(4, 'Majunga', true)];

        $this->assertNull($politique->raison($this->photo('destinations', 'libre-1'), []));
        $this->assertStringContainsString('« Majunga »', (string) $politique->raison($this->photo('destinations', 'utilisee-1'), $galerie));
        $this->assertStringContainsString('Commons', (string) $politique->raison($this->photo('lieux', 'tana-rue'), []));
        $this->assertStringContainsString('annonce', (string) $politique->raison($this->photo('annonces', '3/abc'), []));
    }

    /** Le libellé stocké sur la photo retrouve sa licence — pour pré-remplir la liste. */
    public function test_une_licence_se_retrouve_par_son_libelle(): void
    {
        foreach (PhotoLicence::cases() as $licence) {
            $this->assertSame($licence, PhotoLicence::depuisLibelle($licence->label()));
        }

        $this->assertNull(PhotoLicence::depuisLibelle('CC BY-SA 3.0'), 'Une licence de Commons hors liste ne se confond avec aucune.');
    }
}
