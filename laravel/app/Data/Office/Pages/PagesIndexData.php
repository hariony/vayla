<?php

namespace App\Data\Office\Pages;

use Spatie\LaravelData\Data;

/** Les props de `Office/Pages/Index`. */
final class PagesIndexData extends Data
{
    /**
     * @param  list<PageRowData>  $pages
     * @param  array<string, string>  $groupes  clé → libellé, dans l'ordre du pied de page
     */
    public function __construct(
        public readonly array $pages,
        public readonly array $groupes,
    ) {}
}
