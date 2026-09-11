<?php

namespace App\Services\Office\Stats;

use App\Contracts\Repositories\OfficeStatsRepositoryInterface;
use App\Data\Office\Stats\BarData;
use App\Enums\ListingStatus;
use App\Enums\TrustLevel;

/** Le catalogue **à cet instant** : la période ne s'y applique pas. */
final class CatalogueStats
{
    /** L'ordre des barres : ce qui est en ligne, puis ce qui attend Vayla. */
    private const STATUTS = [ListingStatus::Published, ListingStatus::Submitted, ListingStatus::Draft, ListingStatus::Archived];

    public function __construct(
        private OfficeStatsRepositoryInterface $stats,
    ) {}

    /** @return list<BarData> ce qui attend Vayla prend la terre */
    public function statuts(bool $avecDemo): array
    {
        $nombres = $this->stats->annoncesParStatut($avecDemo);

        return array_map(fn (ListingStatus $s) => new BarData(
            $s->label(),
            $nombres[$s->value] ?? 0,
            $s === ListingStatus::Submitted ? 'terre' : 'encre',
        ), self::STATUTS);
    }

    /**
     * La répartition par barreau, jamais une moyenne. L'échelle est une
     * vérification : le lagon y est chez lui, sauf au niveau 1, qui n'en est pas une.
     *
     * @return list<BarData>
     */
    public function niveaux(bool $avecDemo): array
    {
        $nombres = $this->stats->publieesParNiveau($avecDemo);

        return array_map(fn (TrustLevel $n) => new BarData(
            "{$n->value} · {$n->label()}",
            $nombres[$n->value] ?? 0,
            $n->isVerified() ? 'lagon' : 'gris',
        ), TrustLevel::cases());
    }
}
