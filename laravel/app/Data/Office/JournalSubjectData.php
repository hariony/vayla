<?php

namespace App\Data\Office;

use Spatie\LaravelData\Data;

/** Ce sur quoi porte une ligne du journal, en un mot stable et un identifiant. */
final class JournalSubjectData extends Data
{
    public function __construct(
        public readonly string $type,
        public readonly int $id,
    ) {}
}
