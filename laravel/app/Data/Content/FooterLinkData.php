<?php

namespace App\Data\Content;

use Spatie\LaravelData\Data;

/** Un lien du pied de page vers une page publiée. */
final class FooterLinkData extends Data
{
    public function __construct(
        public readonly string $titre,
        public readonly string $href,
    ) {}
}
