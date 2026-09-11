<?php

namespace App\Services\Content\Pages;

use App\Contracts\Repositories\PageRepositoryInterface;
use App\Contracts\Settings\SettingsStore;
use App\Data\Office\Pages\PageEditData;
use App\Data\Office\Pages\PageFormData;
use App\Data\Office\Pages\PageRowData;
use App\Data\Office\Pages\PagesIndexData;
use App\Enums\PageGroup;
use App\Models\Page;
use App\Support\Pourcent;
use App\Support\PublicUrl;

/** Les écrans des pages éditoriales, dans le back-office. */
final class PageQuery
{
    public function __construct(
        private PageRepositoryInterface $pages,
        private SettingsStore $reglages,
    ) {}

    public function liste(): PagesIndexData
    {
        return new PagesIndexData(
            pages: $this->pages->toutes()->map(fn (Page $p) => PageRowData::fromModel($p))->all(),
            groupes: PageGroup::libelles(),
        );
    }

    /** `null` : une page à créer. */
    public function edition(?Page $page): PageEditData
    {
        return new PageEditData(
            page: $page ? PageFormData::fromModel($page) : null,
            groupes: PageGroup::options(),
            commission: Pourcent::de($this->reglages->commission()),
            site: PublicUrl::de(''),
        );
    }
}
