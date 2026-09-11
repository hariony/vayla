<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/** Une entrée de liste déroulante : ce qui part, ce qui s'affiche, et une précision. */
final class OptionData extends Data
{
    public function __construct(
        public readonly int|string $value,
        public readonly string $label,
        public readonly ?string $detail = null,
    ) {}
}
