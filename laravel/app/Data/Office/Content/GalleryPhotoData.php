<?php

namespace App\Data\Office\Content;

use App\Models\Photo;
use Spatie\LaravelData\Data;

/**
 * Une photo de la galerie d'une destination, ou de la photothèque qui la
 * nourrit. `utilisee` nomme une destination qui la montre déjà.
 */
final class GalleryPhotoData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $key,
        public readonly string $folder,
        public readonly int $width,
        public readonly string $caption,
        public readonly ?string $author,
        public readonly ?string $licence,
        public readonly bool $televersee,
        public readonly ?string $utilisee,
    ) {}

    public static function fromModel(Photo $p, ?string $utilisee): self
    {
        return new self($p->id, $p->key, $p->folder, $p->width, $p->caption, $p->author, $p->licence, $p->folder === 'destinations', $utilisee);
    }
}
