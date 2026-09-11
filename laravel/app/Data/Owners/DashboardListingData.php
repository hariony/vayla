<?php

namespace App\Data\Owners;

use App\Models\Listing;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

/**
 * Un logement sur le tableau de bord. `blocked` : les périodes fermées à
 * venir — c'est ce qui donne au bouton « calendrier » une raison d'être
 * pressé. Un bouton dont on ne sait pas ce qu'il y a derrière ne se presse pas.
 */
final class DashboardListingData extends Data
{
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly ?string $place,
        public readonly ?ListingThumbData $photo,
        public readonly int $price,
        public readonly int $trust,
        public readonly string $trustName,
        public readonly int $blocked,
    ) {}

    public static function fromModel(Listing $l): self
    {
        return new self(
            slug: $l->slug,
            title: $l->title,
            place: $l->destination?->name,
            photo: ListingThumbData::fromModel($l->photos->first()),
            price: (int) $l->price,
            trust: $l->trust_level->value,
            trustName: $l->trust_level->label(),
            blocked: $l->unavailabilities->filter(fn ($u) => $u->ends_on->greaterThanOrEqualTo(Carbon::today()))->count(),
        );
    }
}
