<?php

namespace App\Data\Owners;

use App\Models\Listing;
use Spatie\LaravelData\Data;

/** Le logement dont on règle le calendrier. */
final class CalendarListingData extends Data
{
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly ?string $place,
    ) {}

    public static function fromModel(Listing $l): self
    {
        return new self($l->slug, $l->title, $l->destination?->name);
    }
}
