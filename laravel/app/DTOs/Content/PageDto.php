<?php

namespace App\DTOs\Content;

use App\Enums\PageGroup;

/**
 * Le contenu d'une page éditoriale, nettoyé : les champs vides valent `null`.
 * `slug` n'est qu'une proposition — c'est `PageAddresses` qui décide.
 */
final readonly class PageDto
{
    public function __construct(
        public string $title,
        public ?string $slug,
        public ?string $lede,
        public ?string $body,
        public ?string $seoDescription,
        public ?PageGroup $groupe,
        public ?string $internalNote,
    ) {}
}
