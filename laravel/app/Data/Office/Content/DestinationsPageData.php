<?php

namespace App\Data\Office\Content;

use Spatie\LaravelData\Data;

/** Les props de `Office/Destinations/Index`. */
final class DestinationsPageData extends Data
{
    /** @param  array<int, DestinationRowData>  $destinations */
    public function __construct(public readonly array $destinations) {}
}
