<?php

namespace App\Data\Office;

use Spatie\LaravelData\Data;

/** Un onglet de liste du back-office, **avec son compte** — voir `OfficeTabs.vue`. */
final class TabData extends Data
{
    public function __construct(
        public readonly string $cle,
        public readonly string $label,
        /** `null` quand l'onglet ne se compte pas — les familles du journal. */
        public readonly ?int $nombre = null,
    ) {}
}
