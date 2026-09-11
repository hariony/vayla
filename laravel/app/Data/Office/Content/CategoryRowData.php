<?php

namespace App\Data\Office\Content;

use Spatie\LaravelData\Data;

/** Une catégorie du rail, vue par l'équipe. `structurelle` : un filtre, pas une étiquette. */
final class CategoryRowData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $key,
        public readonly string $label,
        public readonly ?string $icon,
        public readonly bool $sponsored,
        public readonly bool $structurelle,
        public readonly int $listings,
    ) {}
}
