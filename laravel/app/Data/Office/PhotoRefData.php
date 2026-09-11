<?php

namespace App\Data\Office;

use App\Models\Photo;
use Spatie\LaravelData\Data;

/** Une photo telle que `Support/photo.js` l'affiche : sa clé, son dossier, son plus grand palier. */
final class PhotoRefData extends Data
{
    public function __construct(
        public readonly string $key,
        public readonly string $folder,
        public readonly int $width,
        public readonly string $caption,
        public readonly bool $isAi = false,
    ) {}

    public static function fromModel(Photo $p): self
    {
        return new self($p->key, $p->folder, $p->width, $p->caption, (bool) $p->is_ai);
    }
}
