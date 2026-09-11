<?php

namespace App\Data\Office\Content;

use App\Data\OptionData;
use Spatie\LaravelData\Data;

/** Les props de `Office/Destinations/Edit`. */
final class DestinationEditPageData extends Data
{
    /**
     * @param  array<int, GalleryPhotoData>  $galerie  dans l'ordre : la première est la couverture
     * @param  array<int, OptionData>  $zones
     * @param  array<int, OptionData>  $scenes
     * @param  array<int, GalleryPhotoData>  $photos  la photothèque des lieux, moins la galerie
     * @param  array<int, OptionData>  $licences
     */
    public function __construct(
        public readonly ?DestinationFormData $destination,
        public readonly array $galerie,
        public readonly array $zones,
        public readonly array $scenes,
        public readonly array $photos,
        public readonly array $licences,
    ) {}
}
