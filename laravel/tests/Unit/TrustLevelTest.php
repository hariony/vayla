<?php

namespace Tests\Unit;

use App\Enums\TrustLevel;
use PHPUnit\Framework\TestCase;

class TrustLevelTest extends TestCase
{
    public function test_lechelle_a_quatre_barreaux_dans_lordre(): void
    {
        $this->assertSame([1, 2, 3, 4], array_map(
            fn (TrustLevel $l) => $l->value,
            TrustLevel::ladder()
        ));
    }

    /**
     * « Déclarée » n'est pas une vérification : le vert ne commence qu'au
     * niveau 2. Cette règle tient toute la lisibilité de l'échelle.
     */
    public function test_le_niveau_un_nest_pas_une_verification(): void
    {
        $this->assertFalse(TrustLevel::Declared->isVerified());
        $this->assertTrue(TrustLevel::Contact->isVerified());
        $this->assertTrue(TrustLevel::Visited->isVerified());
        $this->assertTrue(TrustLevel::Proven->isVerified());
    }

    public function test_chaque_niveau_porte_une_cle_un_libelle_et_un_resume(): void
    {
        foreach (TrustLevel::ladder() as $level) {
            $this->assertNotEmpty($level->key());
            $this->assertNotEmpty($level->label());
            $this->assertNotEmpty($level->summary());
        }
    }
}
