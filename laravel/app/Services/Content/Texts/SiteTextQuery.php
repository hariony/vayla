<?php

namespace App\Services\Content\Texts;

use App\Contracts\Repositories\SiteTextRepositoryInterface;
use App\Data\Office\Texts\SiteTextFieldData;
use App\Data\Office\Texts\SiteTextGroupData;
use App\Data\Office\Texts\SiteTextsPageData;
use App\Support\PublicUrl;
use App\Support\SiteTextCatalog;

/** L'écran « Textes du site » : chaque groupe, chaque texte, son original et sa version. */
final class SiteTextQuery
{
    public function __construct(
        private SiteTextRepositoryInterface $textes,
    ) {}

    public function page(): SiteTextsPageData
    {
        $modifies = $this->textes->modifies();

        return new SiteTextsPageData(array_map(fn (string $cle) => new SiteTextGroupData(
            cle: $cle,
            titre: SiteTextCatalog::GROUPES[$cle]['titre'],
            note: SiteTextCatalog::GROUPES[$cle]['note'] ?? null,
            lien: PublicUrl::de(SiteTextCatalog::GROUPES[$cle]['ancre']),
            textes: array_map(
                fn (string $texte) => SiteTextFieldData::depuis($texte, SiteTextCatalog::GROUPES[$cle]['textes'][$texte], $modifies->get($texte)),
                array_keys(SiteTextCatalog::GROUPES[$cle]['textes']),
            ),
        ), array_keys(SiteTextCatalog::GROUPES)));
    }
}
