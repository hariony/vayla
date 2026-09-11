<?php

namespace App\Data\Office\Content;

use App\Data\Office\JournalLineData;
use Spatie\LaravelData\Data;

/** Les props de `Office/Settings/Index`. */
final class SettingsPageData extends Data
{
    /** @param  array<int, JournalLineData>  $historique */
    public function __construct(
        public readonly EurRateSettingData $taux,
        public readonly CommissionSettingData $commission,
        public readonly array $historique,
    ) {}
}
