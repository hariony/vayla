<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Throwable;

/**
 * Les réglages de l'équipe, avec le `.env` pour repli.
 *
 * **Jamais une panne pour un réglage.** Une ligne absente, une table pas encore
 * migrée, une base qui ne répond pas : on retombe sur `config('vayla.*')`. Le
 * taux de change s'affiche sur chaque fiche ; une fiche qui échouerait pour un
 * agrément d'affichage serait disproportionné.
 *
 * Lu une fois par requête : le taux est demandé par chaque carte d'une grille.
 */
class SettingsService
{
    public const TAUX_EURO = 'eur_rate';

    public const TAUX_EURO_DATE = 'eur_rate_date';

    public const COMMISSION = 'commission_rate';

    /** @var array<string, string>|null */
    private ?array $valeurs = null;

    public function tauxEuro(): float
    {
        return (float) ($this->lire(self::TAUX_EURO) ?? config('vayla.currency.eur_rate'));
    }

    public function tauxEuroReleveLe(): string
    {
        return (string) ($this->lire(self::TAUX_EURO_DATE) ?? config('vayla.currency.eur_rate_date'));
    }

    /** Le taux de commission **des nouvelles demandes** ; chaque réservation fige le sien. */
    public function commission(): float
    {
        return (float) ($this->lire(self::COMMISSION) ?? config('vayla.commission.rate'));
    }

    public function ecrire(string $cle, string $valeur, ?int $adminId = null): void
    {
        Setting::query()->updateOrCreate(['key' => $cle], ['value' => $valeur, 'admin_id' => $adminId]);
        $this->valeurs = null;
    }

    /** @return array{at: ?string, admin_id: ?int}|null */
    public function trace(string $cle): ?array
    {
        try {
            $ligne = Setting::query()->find($cle);
        } catch (Throwable) {
            return null;
        }

        return $ligne ? ['at' => $ligne->updated_at?->toIso8601String(), 'admin_id' => $ligne->admin_id] : null;
    }

    private function lire(string $cle): ?string
    {
        if ($this->valeurs === null) {
            try {
                $this->valeurs = Setting::query()->pluck('value', 'key')->all();
            } catch (Throwable) {
                $this->valeurs = [];
            }
        }

        return $this->valeurs[$cle] ?? null;
    }
}
