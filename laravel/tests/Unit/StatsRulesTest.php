<?php

namespace Tests\Unit;

use App\Enums\BookingOutcome;
use App\Enums\BookingStatus;
use App\Enums\StatsPeriod;
use App\Services\Support\Median;
use PHPUnit\Framework\TestCase;

/** Les règles pures des statistiques : la médiane, l'issue d'une demande, la période. */
class StatsRulesTest extends TestCase
{
    public function test_la_mediane_prend_le_milieu_et_non_la_moyenne(): void
    {
        $this->assertSame(4.0, Median::de(collect([47, 2, 4])));
        $this->assertSame(3.0, Median::de(collect([2, 4, 1, 47])));
        $this->assertNull(Median::de(collect()));
    }

    /** Un séjour effectué reste une demande acceptée. */
    public function test_chaque_statut_a_une_issue(): void
    {
        foreach (BookingStatus::cases() as $statut) {
            $this->assertInstanceOf(BookingOutcome::class, BookingOutcome::de($statut));
        }

        $this->assertSame(BookingOutcome::Acceptees, BookingOutcome::de(BookingStatus::Completed));
    }

    /** Ni l'attente ni l'annulation ne demandaient au propriétaire de trancher. */
    public function test_seules_les_demandes_tranchees_comptent_dans_le_taux(): void
    {
        $this->assertFalse(BookingOutcome::Attente->tranchee());
        $this->assertFalse(BookingOutcome::Annulees->tranchee());
        $this->assertTrue(BookingOutcome::Expirees->tranchee());
        $this->assertTrue(BookingOutcome::Refusees->tranchee());
    }

    public function test_une_periode_inconnue_vaut_douze_mois(): void
    {
        $this->assertSame(StatsPeriod::Six, StatsPeriod::depuis(6));
        $this->assertSame(StatsPeriod::Douze, StatsPeriod::depuis(500));
        $this->assertSame(StatsPeriod::Douze, StatsPeriod::depuis(0));
        $this->assertSame([6, 12, 24], StatsPeriod::choix());
    }
}
