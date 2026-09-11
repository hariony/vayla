<?php

namespace App\Data\Owners;

use App\Models\Listing;
use Spatie\LaravelData\Data;

/**
 * Un logement dans « Mes logements ». `reviewNote` : ce que Vayla demande
 * quand elle renvoie la fiche — sans ce motif, le propriétaire devinerait ce
 * qui manque.
 */
final class OwnerListingRowData extends Data
{
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly ?string $place,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly string $consigne,
        public readonly ?string $reviewNote,
        public readonly int $price,
        public readonly int $guests,
        public readonly int $bedrooms,
        public readonly int $trust,
        public readonly string $trustName,
        public readonly int $photos,
        public readonly ?ListingThumbData $photo,
    ) {}

    public static function fromModel(Listing $l): self
    {
        return new self(
            slug: $l->slug,
            title: $l->title,
            place: $l->destination?->name,
            status: $l->status->value,
            statusLabel: $l->status->label(),
            consigne: $l->status->consigne(),
            reviewNote: $l->review_note,
            price: (int) $l->price,
            guests: (int) $l->guests,
            bedrooms: (int) $l->bedrooms,
            trust: $l->trust_level->value,
            trustName: $l->trust_level->label(),
            photos: $l->photos->count(),
            photo: ListingThumbData::fromModel($l->photos->first()),
        );
    }
}
