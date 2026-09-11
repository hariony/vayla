<?php

namespace App\Data\Listings;

use App\Models\Amenity;
use App\Models\Listing;
use App\Models\Photo;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

/**
 * Une annonce, pour son formulaire — celui du propriétaire comme celui du
 * back-office. Les champs de saisie gardent le nom de leur colonne : c'est ce
 * que le formulaire renvoie. `modifiable` : faux une fois l'annonce vérifiée,
 * et l'écran **dit** ce qui reste libre au lieu de griser sans expliquer.
 */
final class ListingFormData extends Data
{
    /**
     * @param  list<AmenityPickData>  $amenities
     * @param  list<ListingPhotoData>  $photos
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly string $consigne,
        public readonly ?string $reviewNote,
        public readonly bool $modifiable,
        public readonly int $trust,
        public readonly string $trustName,
        public readonly string $title,
        #[MapOutputName('destination_id')]
        public readonly ?int $destinationId,
        public readonly string $kind,
        public readonly ?string $summary,
        public readonly ?string $description,
        public readonly int $guests,
        public readonly int $bedrooms,
        public readonly int $beds,
        public readonly int $bathrooms,
        public readonly ?int $surface,
        public readonly int $price,
        #[MapOutputName('min_nights')]
        public readonly int $minNights,
        #[MapOutputName('max_nights')]
        public readonly ?int $maxNights,
        #[MapOutputName('check_in_from')]
        public readonly string $checkInFrom,
        #[MapOutputName('check_out_before')]
        public readonly string $checkOutBefore,
        #[MapOutputName('pets_allowed')]
        public readonly bool $petsAllowed,
        #[MapOutputName('smoking_allowed')]
        public readonly bool $smokingAllowed,
        #[MapOutputName('events_allowed')]
        public readonly bool $eventsAllowed,
        public readonly array $amenities,
        public readonly array $photos,
    ) {}

    /** L'annonce avec `amenities` et `photos` chargées. */
    public static function fromModel(Listing $l): self
    {
        return new self(...[
            'slug' => $l->slug,
            'status' => $l->status->value,
            'statusLabel' => $l->status->label(),
            'consigne' => $l->status->consigne(),
            'reviewNote' => $l->review_note,
            'modifiable' => $l->status->estModifiable(),
            'trust' => $l->trust_level->value,
            'trustName' => $l->trust_level->label(),
            ...self::fiche($l),
            'amenities' => $l->amenities->map(fn (Amenity $a) => new AmenityPickData($a->id, (bool) $a->pivot->highlight))->values()->all(),
            'photos' => $l->photos->map(fn (Photo $p) => ListingPhotoData::fromModel($p))->sortBy('position')->values()->all(),
        ]);
    }

    /**
     * Les champs, par nom — pour que le formulaire du back-office les reprenne
     * tels quels et n'ajoute que ce qui lui est propre.
     *
     * @return array<string, mixed>
     */
    public function champs(): array
    {
        return [
            'slug' => $this->slug,
            'status' => $this->status,
            'statusLabel' => $this->statusLabel,
            'consigne' => $this->consigne,
            'reviewNote' => $this->reviewNote,
            'modifiable' => $this->modifiable,
            'trust' => $this->trust,
            'trustName' => $this->trustName,
            'title' => $this->title,
            'destinationId' => $this->destinationId,
            'kind' => $this->kind,
            'summary' => $this->summary,
            'description' => $this->description,
            'guests' => $this->guests,
            'bedrooms' => $this->bedrooms,
            'beds' => $this->beds,
            'bathrooms' => $this->bathrooms,
            'surface' => $this->surface,
            'price' => $this->price,
            'minNights' => $this->minNights,
            'maxNights' => $this->maxNights,
            'checkInFrom' => $this->checkInFrom,
            'checkOutBefore' => $this->checkOutBefore,
            'petsAllowed' => $this->petsAllowed,
            'smokingAllowed' => $this->smokingAllowed,
            'eventsAllowed' => $this->eventsAllowed,
            'amenities' => $this->amenities,
            'photos' => $this->photos,
        ];
    }

    /** @return array<string, mixed> les champs de saisie */
    private static function fiche(Listing $l): array
    {
        return [
            'title' => $l->title,
            'destinationId' => $l->destination_id,
            'kind' => $l->kind->value,
            'summary' => $l->summary,
            'description' => $l->description,
            'guests' => (int) $l->guests,
            'bedrooms' => (int) $l->bedrooms,
            'beds' => (int) $l->beds,
            'bathrooms' => (int) $l->bathrooms,
            'surface' => $l->surface,
            'price' => (int) $l->price,
            'minNights' => (int) $l->min_nights,
            'maxNights' => $l->max_nights,
            'checkInFrom' => substr((string) $l->check_in_from, 0, 5),
            'checkOutBefore' => substr((string) $l->check_out_before, 0, 5),
            'petsAllowed' => (bool) $l->pets_allowed,
            'smokingAllowed' => (bool) $l->smoking_allowed,
            'eventsAllowed' => (bool) $l->events_allowed,
        ];
    }
}
