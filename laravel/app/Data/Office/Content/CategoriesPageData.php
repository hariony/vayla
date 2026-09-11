<?php

namespace App\Data\Office\Content;

use App\Data\OptionData;
use Spatie\LaravelData\Data;

/** Les props de `Office/Categories/Index`. */
final class CategoriesPageData extends Data
{
    /**
     * @param  array<int, CategoryRowData>  $categories
     * @param  array<int, OptionData>  $icones
     */
    public function __construct(
        public readonly array $categories,
        public readonly array $icones,
    ) {}
}
