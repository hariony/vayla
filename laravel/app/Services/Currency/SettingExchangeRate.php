<?php

namespace App\Services\Currency;

use App\Services\Settings\SettingsService;

/**
 * Le taux tenu depuis le back-office, le `.env` pour repli.
 *
 * C'est la couture que `ExchangeRateProvider` attendait : seule la liaison
 * d'`AppServiceProvider` a changé. Le jour où une API de change arrive, elle
 * écrira dans le même réglage — et l'écran « Réglages » dira d'où vient le
 * chiffre.
 */
class SettingExchangeRate implements ExchangeRateProvider
{
    public function __construct(
        private SettingsService $reglages,
    ) {}

    public function ariaryParEuro(): float
    {
        return $this->reglages->tauxEuro();
    }

    public function releveLe(): string
    {
        return $this->reglages->tauxEuroReleveLe();
    }
}
