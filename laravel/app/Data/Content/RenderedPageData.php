<?php

namespace App\Data\Content;

use Spatie\LaravelData\Data;

/** Le Markdown d'une page rendu en HTML sûr, et son sommaire. Sert aussi l'aperçu du back-office. */
final class RenderedPageData extends Data
{
    /** @param  list<PageHeadingData>  $sommaire */
    public function __construct(
        public readonly string $html,
        public readonly array $sommaire,
    ) {}
}
