<?php

namespace App\Data\Photos;

use App\Data\Office\ListFilterData;
use App\Data\Office\PaginationData;
use App\Data\Office\TabData;
use App\Data\OptionData;
use Spatie\LaravelData\Data;

/** Les props de `Office/Photos/Index`. */
final class PhotoLibraryPageData extends Data
{
    /**
     * @param  array<int, PhotoLibraryItemData>  $photos
     * @param  array<int, TabData>  $onglets
     * @param  array<int, OptionData>  $licences
     * @param  array<int, OptionData>  $destinations
     */
    public function __construct(
        public readonly array $photos,
        public readonly ?PhotoLibraryItemData $ouverte,
        public readonly PaginationData $meta,
        public readonly array $onglets,
        public readonly ListFilterData $filtre,
        public readonly DiskUsageData $disque,
        public readonly array $licences,
        public readonly array $destinations,
    ) {}
}
