<?php

namespace App\Data\Photos;

use Spatie\LaravelData\Data;

/**
 * Une photo de la photothèque, mise à plat pour l'écran : son image, son
 * crédit, où elle apparaît, ce qu'elle pèse et ce qu'on peut en faire.
 * `key`, `folder` et `width` sont ceux qu'attend `Support/photo.js`.
 */
final class PhotoLibraryItemData extends Data
{
    /**
     * @param  array<int, PhotoUsageData>  $usages
     * @param  array<int, int>  $paliers
     */
    public function __construct(
        public readonly int $id,
        public readonly string $key,
        public readonly string $folder,
        public readonly int $width,
        public readonly string $caption,
        public readonly ?string $author,
        public readonly ?string $licence,
        public readonly ?string $licenceUrl,
        public readonly ?string $licenceCle,
        public readonly ?string $sourceUrl,
        public readonly string $provenance,
        public readonly string $provenanceLabel,
        public readonly array $usages,
        public readonly int $poids,
        public readonly array $paliers,
        public readonly ?string $ajoutee,
        public readonly PhotoPermissionsData $peut,
        public readonly ?string $pourquoiPasSupprimer,
    ) {}
}
