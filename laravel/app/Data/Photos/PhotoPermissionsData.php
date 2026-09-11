<?php

namespace App\Data\Photos;

use Spatie\LaravelData\Data;

/** Ce que l'équipe a le droit de faire d'une photo — déduit de sa provenance et de ses usages. */
final class PhotoPermissionsData extends Data
{
    public function __construct(
        public readonly bool $legende,
        public readonly bool $credit,
        public readonly bool $licence,
        public readonly bool $supprimer,
    ) {}
}
