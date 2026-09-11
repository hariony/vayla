<?php

namespace App\Data\Owners;

use Spatie\LaravelData\Data;

/** Les props de `Owner/Profile` : l'adresse que le code vient de prouver, rappelée au-dessus de la fiche. */
final class OwnerProfilePageData extends Data
{
    public function __construct(
        public readonly string $email,
    ) {}
}
