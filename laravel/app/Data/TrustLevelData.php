<?php

namespace App\Data;

use App\Enums\TrustLevel;
use Spatie\LaravelData\Data;

class TrustLevelData extends Data
{
    public function __construct(
        public readonly int $level,
        public readonly string $key,
        public readonly string $name,
        public readonly string $summary,
    ) {}

    public static function fromEnum(TrustLevel $level): self
    {
        return new self(
            level: $level->value,
            key: $level->key(),
            name: $level->label(),
            summary: $level->summary(),
        );
    }
}
