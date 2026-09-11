<?php

namespace App\Data\Office;

use App\Models\Listing;
use App\Support\PublicUrl;
use Spatie\LaravelData\Data;

/** Une annonce dans une file du back-office. */
final class ListingRowData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $title,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly int $trustLevel,
        public readonly string $trustLabel,
        public readonly ?string $destination,
        public readonly ?string $region,
        public readonly int $price,
        public readonly int $guests,
        public readonly int $photos,
        public readonly ?PhotoRefData $cover,
        public readonly bool $isDemo,
        public readonly ?string $updatedAt,
        public readonly string $publicUrl,
        public readonly ?ListingOwnerData $owner,
    ) {}

    public static function fromModel(Listing $l, bool $avecProprietaire = true): self
    {
        $couverture = $l->photos->first();

        return new self(
            id: $l->id,
            slug: $l->slug,
            title: $l->title,
            status: $l->status->value,
            statusLabel: $l->status->libelleFile(pluriel: false),
            trustLevel: $l->trust_level->value,
            trustLabel: $l->trust_level->label(),
            destination: $l->destination?->name,
            region: $l->destination?->region,
            price: (int) $l->price,
            guests: (int) $l->guests,
            photos: $l->photos->count(),
            cover: $couverture ? PhotoRefData::fromModel($couverture) : null,
            isDemo: (bool) $l->is_demo,
            updatedAt: $l->updated_at?->toIso8601String(),
            publicUrl: PublicUrl::de('/logements/'.$l->slug),
            owner: $avecProprietaire && $l->owner
                ? new ListingOwnerData($l->owner->id, $l->owner->name, $l->owner->telephoneVerifie())
                : null,
        );
    }

    /**
     * Les champs, par nom — pour qu'une fiche détaillée les reprenne tels quels
     * et n'ajoute que ce qui lui est propre.
     *
     * @return array<string, mixed>
     */
    public function champs(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'status' => $this->status,
            'statusLabel' => $this->statusLabel,
            'trustLevel' => $this->trustLevel,
            'trustLabel' => $this->trustLabel,
            'destination' => $this->destination,
            'region' => $this->region,
            'price' => $this->price,
            'guests' => $this->guests,
            'photos' => $this->photos,
            'cover' => $this->cover,
            'isDemo' => $this->isDemo,
            'updatedAt' => $this->updatedAt,
            'publicUrl' => $this->publicUrl,
            'owner' => $this->owner,
        ];
    }
}
