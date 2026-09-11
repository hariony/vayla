<?php

namespace App\Data\Office;

use Spatie\LaravelData\Data;

/** Un propriétaire nommé en passant — le lien vers sa fiche. */
final class OwnerRefData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}
