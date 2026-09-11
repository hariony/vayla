<?php

namespace Tests\Unit;

use App\Enums\PositionShift;
use App\Services\Support\PositionSwapper;
use App\Services\Support\UniqueSlug;
use PHPUnit\Framework\TestCase;

/** Les deux rouages purs du contenu : ranger une liste, trouver une clé libre. */
class PositionSwapperTest extends TestCase
{
    public function test_une_ligne_s_echange_avec_sa_voisine(): void
    {
        $positions = new PositionSwapper;

        $this->assertSame([2, 1, 3], $positions->deplacer([1, 2, 3], 2, PositionShift::Haut));
        $this->assertSame([1, 3, 2], $positions->deplacer([1, 2, 3], 2, PositionShift::Bas));
    }

    /** Au bord, ou pour une ligne inconnue, rien ne bouge — et on le sait. */
    public function test_au_bord_rien_ne_bouge(): void
    {
        $positions = new PositionSwapper;

        $this->assertNull($positions->deplacer([1, 2, 3], 1, PositionShift::Haut));
        $this->assertNull($positions->deplacer([1, 2, 3], 3, PositionShift::Bas));
        $this->assertNull($positions->deplacer([1, 2, 3], 9, PositionShift::Haut));
    }

    public function test_une_cle_prise_prend_un_numero(): void
    {
        $prises = ['nosy-be', 'nosy-be-2'];

        $this->assertSame('nosy-be-3', (new UniqueSlug)->pour('Nosy Be', fn (string $s) => in_array($s, $prises, true)));
        $this->assertSame('sainte-marie', (new UniqueSlug)->pour('Sainte-Marie', fn () => false));
    }
}
