<?php

namespace App\Data\Photos;

use Spatie\LaravelData\Data;

/** Le poids de la photothèque, par dossier : ce que coûte le stockage, et d'où il vient. */
final class DiskUsageData extends Data
{
    public function __construct(
        public readonly int $total,
        public readonly int $lieux,
        public readonly int $destinations,
        public readonly int $annonces,
    ) {}
}
