<?php

namespace App\Data\Office;

use Spatie\LaravelData\Data;

/** Une annonce nommée en passant — le lien vers sa fiche. */
final class ListingRefData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
    ) {}
}
