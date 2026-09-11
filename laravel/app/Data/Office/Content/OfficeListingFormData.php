<?php

namespace App\Data\Office\Content;

use App\Data\Listings\AmenityPickData;
use App\Data\Listings\ListingFormData;
use App\Data\Listings\ListingPhotoData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

/**
 * Une annonce pour le formulaire du back-office : **le même que celui du
 * propriétaire** (`ListingFormData`), plus ce que seule l'équipe touche — la
 * mise en avant, les catégories — et de quoi voir la fiche publique.
 */
final class OfficeListingFormData extends Data
{
    /**
     * @param  list<AmenityPickData>  $amenities
     * @param  list<ListingPhotoData>  $photos
     * @param  list<int>  $categories
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
        public readonly int $id,
        public readonly bool $featured,
        public readonly array $categories,
        public readonly bool $isDemo,
        public readonly string $publicUrl,
    ) {}

    /** @param  list<int>  $categories */
    public static function depuis(ListingFormData $fiche, int $id, bool $featured, array $categories, bool $isDemo, string $publicUrl): self
    {
        return new self(...[
            ...$fiche->champs(),
            'id' => $id,
            'featured' => $featured,
            'categories' => $categories,
            'isDemo' => $isDemo,
            'publicUrl' => $publicUrl,
        ]);
    }
}
