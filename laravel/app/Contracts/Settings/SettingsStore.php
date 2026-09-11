<?php

namespace App\Contracts\Settings;

use App\DTOs\Settings\SettingTraceDto;

/**
 * Les réglages tenus depuis le back-office, avec le `.env` pour repli : une
 * ligne absente, ou une table pas encore migrée, ne fait jamais tomber une page.
 */
interface SettingsStore
{
    public const TAUX_EURO = 'eur_rate';

    public const TAUX_EURO_DATE = 'eur_rate_date';

    public const COMMISSION = 'commission_rate';

    public function tauxEuro(): float;

    public function tauxEuroReleveLe(): string;

    /** Le taux de commission **des nouvelles demandes** ; chaque réservation fige le sien. */
    public function commission(): float;

    public function ecrire(string $cle, string $valeur, ?int $adminId = null): void;

    /** `null` si le réglage n'a jamais été changé au back-office : c'est le `.env` qui parle. */
    public function trace(string $cle): ?SettingTraceDto;
}
