<?php

namespace App\Data\Office\Dashboard;

use Spatie\LaravelData\Data;

/** Une file qui attend quelqu'un : combien, où aller, et ce que ça veut dire. */
final class TodoData extends Data
{
    public function __construct(
        public readonly string $cle,
        public readonly int $nombre,
        public readonly string $label,
        public readonly string $href,
        public readonly string $detail,
    ) {}
}
