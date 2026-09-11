<?php

namespace App\Services\Settings;

use App\Contracts\Repositories\SettingRepositoryInterface;
use App\Contracts\Settings\SettingsStore;
use App\DTOs\Settings\SettingTraceDto;
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
class SettingsService implements SettingsStore
{
    /** @var array<string, string>|null */
    private ?array $valeurs = null;

    public function __construct(private SettingRepositoryInterface $reglages) {}

    public function tauxEuro(): float
    {
        return (float) ($this->lire(self::TAUX_EURO) ?? config('vayla.currency.eur_rate'));
    }

    public function tauxEuroReleveLe(): string
    {
        return (string) ($this->lire(self::TAUX_EURO_DATE) ?? config('vayla.currency.eur_rate_date'));
    }

    public function commission(): float
    {
        return (float) ($this->lire(self::COMMISSION) ?? config('vayla.commission.rate'));
    }

    public function ecrire(string $cle, string $valeur, ?int $adminId = null): void
    {
        $this->reglages->ecrire($cle, $valeur, $adminId);
        $this->valeurs = null;
    }

    public function trace(string $cle): ?SettingTraceDto
    {
        try {
            $ligne = $this->reglages->trouver($cle);
        } catch (Throwable) {
            return null;
        }

        return $ligne ? new SettingTraceDto($ligne->updated_at?->toIso8601String(), $ligne->admin_id) : null;
    }

    private function lire(string $cle): ?string
    {
        if ($this->valeurs === null) {
            try {
                $this->valeurs = $this->reglages->valeurs();
            } catch (Throwable) {
                $this->valeurs = [];
            }
        }

        return $this->valeurs[$cle] ?? null;
    }
}
