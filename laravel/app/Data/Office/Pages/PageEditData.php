<?php

namespace App\Data\Office\Pages;

use App\Data\OptionData;
use Spatie\LaravelData\Data;

/**
 * Les props de `Office/Pages/Edit`. `page` est nul à la création ;
 * `commission` montre ce que `{commission}` deviendra à l'affichage.
 */
final class PageEditData extends Data
{
    /** @param  list<OptionData>  $groupes */
    public function __construct(
        public readonly ?PageFormData $page,
        public readonly array $groupes,
        public readonly string $commission,
        public readonly string $site,
    ) {}
}
