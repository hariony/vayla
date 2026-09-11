<?php

namespace App\DTOs\Listings;

use App\Enums\PropertyType;

/**
 * La fiche d'une annonce, champ par champ. **Ni `trust_level`, ni `status`, ni
 * `slug`** : ils ont leurs gestes à eux — le niveau et la publication dans
 * `ModerationService`, le slug qui naît du titre et ne bouge plus.
 */
final readonly class ListingFicheDto
{
    public function __construct(
        public string $title,
        public int $destinationId,
        public PropertyType $kind,
        public ?string $summary,
        public ?string $description,
        public int $guests,
        public int $bedrooms,
        public int $beds,
        public int $bathrooms,
        public ?int $surface,
        public int $price,
        public int $minNights,
        public ?int $maxNights,
        public string $checkInFrom,
        public string $checkOutBefore,
        public bool $petsAllowed,
        public bool $smokingAllowed,
        public bool $eventsAllowed,
        /** Seul le back-office met en avant ; `null` quand le formulaire ne le porte pas. */
        public ?bool $featured = null,
    ) {}

    /**
     * Les colonnes de `listings`, pour les deux dépôts qui écrivent une fiche
     * (celui du propriétaire, celui du back-office). `featured` n'y est que si
     * le formulaire le porte.
     *
     * @return array<string, mixed>
     */
    public function attributs(): array
    {
        return array_filter([
            'title' => $this->title,
            'destination_id' => $this->destinationId,
            'kind' => $this->kind->value,
            'summary' => $this->summary,
            'description' => $this->description,
            'guests' => $this->guests,
            'bedrooms' => $this->bedrooms,
            'beds' => $this->beds,
            'bathrooms' => $this->bathrooms,
            'surface' => $this->surface,
            'price' => $this->price,
            'min_nights' => $this->minNights,
            'max_nights' => $this->maxNights,
            'check_in_from' => $this->checkInFrom,
            'check_out_before' => $this->checkOutBefore,
            'pets_allowed' => $this->petsAllowed,
            'smoking_allowed' => $this->smokingAllowed,
            'events_allowed' => $this->eventsAllowed,
            'featured' => $this->featured,
        ], fn ($v, string $cle) => $cle !== 'featured' || $v !== null, ARRAY_FILTER_USE_BOTH);
    }
}
