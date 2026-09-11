<?php

namespace App\Data\Office\Content;

use App\Models\Category;
use Spatie\LaravelData\Data;

/** Une catégorie qu'on peut poser sur une annonce (ni « Tout », ni « Séjour confirmé »). */
final class EditorialCategoryData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $label,
        public readonly bool $sponsored,
    ) {}

    public static function fromModel(Category $c): self
    {
        return new self($c->id, $c->label, (bool) $c->sponsored);
    }
}
