<?php

namespace App\Data\Owners;

use App\Models\Photo;
use Spatie\LaravelData\Data;

/**
 * La photo de couverture d'un logement, pour une vignette. L'objet complet,
 * pas la clé : le `srcset` du front a besoin du dossier et de la largeur
 * réellement disponible.
 */
final class ListingThumbData extends Data
{
    public function __construct(
        public readonly string $key,
        public readonly string $folder,
        public readonly int $width,
    ) {}

    public static function fromModel(?Photo $photo): ?self
    {
        return $photo ? new self($photo->key, $photo->folder, (int) $photo->width) : null;
    }
}
