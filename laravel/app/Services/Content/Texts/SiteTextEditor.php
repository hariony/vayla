<?php

namespace App\Services\Content\Texts;

use App\Contracts\Office\ActionJournal;
use App\Contracts\Repositories\SiteTextRepositoryInterface;
use App\DTOs\Content\SiteTextsDto;
use App\Enums\AdminActionKind;
use App\Models\Admin;
use App\Support\SiteTextCatalog;

/**
 * Réécrire les textes d'un groupe. **Une valeur égale à l'original — ou vide —
 * supprime la ligne** : c'est ce que fait « Rétablir », et c'est ce qui garde
 * la base réduite à ce qui a vraiment été réécrit.
 */
final class SiteTextEditor
{
    public function __construct(
        private SiteTextRepositoryInterface $textes,
        private SiteTexts $site,
        private ActionJournal $journal,
    ) {}

    /** @return list<string> les libellés des textes changés */
    public function enregistrer(Admin $par, SiteTextsDto $saisie): array
    {
        $groupe = SiteTextCatalog::GROUPES[$saisie->groupe];
        $actuels = $this->site->tous();
        $changes = [];

        foreach ($groupe['textes'] as $cle => $definition) {
            $valeur = $saisie->textes[$cle] ?? null;

            if ($valeur === null || $valeur === ($actuels[$cle] ?? null)) {
                continue;
            }

            $valeur === $definition['defaut'] || $valeur === ''
                ? $this->textes->effacer($cle)
                : $this->textes->ecrire($par, $cle, $valeur);

            $changes[] = $definition['label'];
        }

        if ($changes !== []) {
            $this->site->oublier();
            $this->journal->consigner($par, AdminActionKind::SiteTextsChanged, null, $groupe['titre'].' : '.implode(', ', $changes).'.');
        }

        return $changes;
    }
}
