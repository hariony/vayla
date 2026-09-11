<?php

namespace App\Data\Office\Listings;

use App\Data\Office\ListingOwnerData;
use App\Data\Office\ListingRowData;
use App\Data\Office\PhotoRefData;
use App\Models\Amenity;
use App\Models\Listing;
use App\Models\Photo;
use Spatie\LaravelData\Data;

/**
 * Une annonce, pour sa modération : la ligne de file, plus tout ce que l'appel
 * de vérification relit. `photos` y est **la galerie** — dans la ligne, c'est
 * leur nombre.
 */
final class ListingDetailData extends Data
{
    /**
     * @param  array<int, PhotoRefData>  $photos
     * @param  array<int, AmenityGroupListData>  $equipements
     */
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
        public readonly array $photos,
        public readonly ?PhotoRefData $cover,
        public readonly bool $isDemo,
        public readonly ?string $updatedAt,
        public readonly string $publicUrl,
        public readonly ?ListingOwnerData $owner,
        public readonly ?string $kind,
        public readonly ?string $summary,
        public readonly ?string $description,
        public readonly CapacityData $capacite,
        public readonly StayRulesSummaryData $sejour,
        public readonly array $equipements,
        public readonly ?string $reviewNote,
        public readonly int $confirmations,
        public readonly int $aVenir,
    ) {}

    public static function fromModel(Listing $l, int $confirmations, int $aVenir): self
    {
        return new self(...[
            ...ListingRowData::fromModel($l)->champs(),
            'photos' => $l->photos->map(fn (Photo $p) => PhotoRefData::fromModel($p))->all(),
            'kind' => $l->kind?->label(),
            'summary' => $l->summary,
            'description' => $l->description,
            'capacite' => new CapacityData($l->guests, $l->bedrooms, $l->beds, $l->bathrooms, $l->surface),
            'sejour' => new StayRulesSummaryData(
                $l->min_nights, $l->max_nights, $l->check_in_from, $l->check_out_before,
                (bool) $l->pets_allowed, (bool) $l->smoking_allowed, (bool) $l->events_allowed,
            ),
            'equipements' => self::equipements($l),
            'reviewNote' => $l->review_note,
            'confirmations' => $confirmations,
            'aVenir' => $aVenir,
        ]);
    }

    /** @return array<int, AmenityGroupListData> par rubrique, dans l'ordre des rubriques */
    private static function equipements(Listing $l): array
    {
        return $l->amenities
            ->sortBy(fn (Amenity $a) => [$a->group->position(), $a->position])
            ->groupBy(fn (Amenity $a) => $a->group->label())
            ->map(fn ($groupe, $label) => new AmenityGroupListData(
                $label,
                $groupe->map(fn (Amenity $a) => new AmenityItemData($a->label, (bool) $a->pivot->highlight, $a->pivot->note))->values()->all(),
            ))
            ->values()
            ->all();
    }
}
