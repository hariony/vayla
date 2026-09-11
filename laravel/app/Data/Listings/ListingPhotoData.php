<?php

namespace App\Data\Listings;

use App\Models\Photo;
use Spatie\LaravelData\Data;

/** Une photo de la galerie d'une annonce, à sa place : la position 0 est la couverture. */
final class ListingPhotoData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $key,
        public readonly string $folder,
        public readonly int $width,
        public readonly ?string $caption,
        public readonly int $position,
    ) {}

    public static function fromModel(Photo $p): self
    {
        return new self($p->id, $p->key, $p->folder, (int) $p->width, $p->caption, (int) $p->pivot->position);
    }
}
