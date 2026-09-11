<?php

namespace App\Data\Office\Listings;

use Spatie\LaravelData\Data;

/** Ce que la modération peut faire de la fiche — et pourquoi pas, quand elle ne peut pas. */
final class PublicationData extends Data
{
    public function __construct(
        public readonly bool $possible,
        public readonly ?string $raison,
        public readonly bool $renvoyable,
        public readonly bool $archivable,
    ) {}
}
