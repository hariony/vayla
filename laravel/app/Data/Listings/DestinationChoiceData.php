<?php

namespace App\Data\Listings;

use App\Models\Destination;
use Spatie\LaravelData\Data;

/** Une destination à choisir dans la liste : « Nosy Be — Diana ». */
final class DestinationChoiceData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $label,
    ) {}

    public static function fromModel(Destination $d): self
    {
        return new self($d->id, "{$d->name} — {$d->region}");
    }
}
