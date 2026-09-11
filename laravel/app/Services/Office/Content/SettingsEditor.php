<?php

namespace App\Services\Office\Content;

use App\Contracts\Office\ActionJournal;
use App\Contracts\Settings\SettingsStore;
use App\DTOs\Content\EurRateDto;
use App\Enums\AdminActionKind;
use App\Models\Admin;

/** Changer un réglage — et le dire au journal, avant et après. */
final class SettingsEditor
{
    public function __construct(
        private SettingsStore $reglages,
        private ActionJournal $journal,
    ) {}

    /** Le taux porte sa date, **saisie avec lui** — jamais déduite de `now()`. */
    public function changerTauxEuro(Admin $admin, EurRateDto $saisie): void
    {
        $avant = $this->reglages->tauxEuro();

        $this->reglages->ecrire(SettingsStore::TAUX_EURO, (string) $saisie->taux, $admin->id);
        $this->reglages->ecrire(SettingsStore::TAUX_EURO_DATE, $saisie->releveLe, $admin->id);

        $this->journal->consigner($admin, AdminActionKind::SettingChanged, null,
            'Taux de change : 1 € = '.$this->entier($avant).' Ar → '.$this->entier($saisie->taux)." Ar, relevé le {$saisie->releveLe}.");
    }

    /**
     * **Le nouveau taux ne vaut que pour les nouvelles demandes.** Chaque
     * réservation fige le sien : une facture qui change après coup est une
     * facture qu'on ne paie pas.
     */
    public function changerCommission(Admin $admin, float $pourcent): void
    {
        $avant = $this->reglages->commission() * 100;

        $this->reglages->ecrire(SettingsStore::COMMISSION, (string) round($pourcent / 100, 4), $admin->id);

        $this->journal->consigner($admin, AdminActionKind::SettingChanged, null,
            'Commission : '.$this->pourcent($avant).' % → '.$this->pourcent($pourcent).' % pour les nouvelles demandes.');
    }

    private function entier(float $n): string
    {
        return number_format($n, 0, ',', "\u{00A0}");
    }

    private function pourcent(float $n): string
    {
        return rtrim(rtrim(number_format($n, 1, ',', ''), '0'), ',');
    }
}
