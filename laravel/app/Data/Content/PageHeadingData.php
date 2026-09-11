<?php

namespace App\Data\Content;

use Spatie\LaravelData\Data;

/** Un intertitre de page, pour le sommaire : son ancre et son texte. */
final class PageHeadingData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
    ) {}
}
