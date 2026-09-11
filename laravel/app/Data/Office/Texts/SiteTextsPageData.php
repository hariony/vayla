<?php

namespace App\Data\Office\Texts;

use Spatie\LaravelData\Data;

/** Les props de `Office/Texts/Index`. */
final class SiteTextsPageData extends Data
{
    /** @param  list<SiteTextGroupData>  $groupes */
    public function __construct(
        public readonly array $groupes,
    ) {}
}
