<?php

namespace App\Data\Office\Shell;

use Spatie\LaravelData\Data;

/** Les quatre files qui attendent quelqu'un, comptées pour la colonne du back-office. */
final class OfficeCountersData extends Data
{
    public function __construct(
        public readonly int $annonces,
        public readonly int $reservations,
        public readonly int $whatsapp,
        public readonly int $demandes,
    ) {}
}
