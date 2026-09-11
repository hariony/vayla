<?php

namespace App\Services\Office\Content;

use App\Contracts\Office\JournalReader;
use App\Contracts\Repositories\AdminRepositoryInterface;
use App\Contracts\Settings\SettingsStore;
use App\Data\Office\Content\CommissionSettingData;
use App\Data\Office\Content\EurRateSettingData;
use App\Data\Office\Content\SettingsPageData;
use App\Data\Office\Content\SettingTraceData;
use App\Enums\AdminActionKind;

/** Les réglages en vigueur, d'où ils viennent, et leurs dix derniers changements. */
final class SettingsQuery
{
    public function __construct(
        private SettingsStore $reglages,
        private AdminRepositoryInterface $admins,
        private JournalReader $journal,
    ) {}

    public function page(): SettingsPageData
    {
        return new SettingsPageData(
            taux: new EurRateSettingData(
                valeur: $this->reglages->tauxEuro(),
                date: $this->reglages->tauxEuroReleveLe(),
                trace: $this->trace(SettingsStore::TAUX_EURO),
                source: $this->source(SettingsStore::TAUX_EURO),
            ),
            commission: new CommissionSettingData(
                pourcent: round($this->reglages->commission() * 100, 2),
                trace: $this->trace(SettingsStore::COMMISSION),
                source: $this->source(SettingsStore::COMMISSION),
            ),
            historique: $this->journal->dernieres(10, AdminActionKind::SettingChanged),
        );
    }

    private function trace(string $cle): ?SettingTraceData
    {
        $trace = $this->reglages->trace($cle);

        return $trace ? new SettingTraceData($trace->at, $trace->adminId ? $this->admins->nom($trace->adminId) : null) : null;
    }

    /** `back-office` s'il a été réglé ici, `configuration` si c'est encore le `.env` qui parle. */
    private function source(string $cle): string
    {
        return $this->reglages->trace($cle) ? 'back-office' : 'configuration';
    }
}
