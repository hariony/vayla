<?php

namespace App\Http\Requests\Office;

use App\DTOs\Listings\AmenityChoiceDto;
use App\DTOs\Listings\ListingContentDto;
use App\DTOs\Listings\ListingFicheDto;
use App\Enums\PropertyType;
use App\Http\Requests\OwnerListingRequest;

/**
 * Le contenu d'une annonce, saisi par Vayla.
 *
 * **Les mêmes bornes que la fiche du propriétaire, héritées et non recopiées** :
 * un tarif refusé à l'un ne doit pas passer par l'autre. S'y ajoutent ce qui
 * n'appartient qu'à Vayla — la mise en avant et les catégories du rail.
 * `trust_level` et `status` n'y sont toujours pas : ils ont leurs gestes à eux.
 */
class OfficeListingRequest extends OwnerListingRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'featured' => ['boolean'],
            'categories' => ['array', 'max:20'],
            'categories.*' => ['integer', 'exists:categories,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge(['featured' => $this->boolean('featured')]);
    }

    public function toDto(): ListingContentDto
    {
        $texte = fn (string $champ) => $this->filled($champ) ? trim($this->string($champ)->toString()) : null;
        $entier = fn (string $champ) => $this->filled($champ) ? $this->integer($champ) : null;

        return new ListingContentDto(
            fiche: new ListingFicheDto(
                title: trim($this->string('title')->toString()),
                destinationId: $this->integer('destination_id'),
                kind: $this->enum('kind', PropertyType::class),
                summary: $texte('summary'),
                description: $texte('description'),
                guests: $this->integer('guests'),
                bedrooms: $this->integer('bedrooms'),
                beds: $this->integer('beds'),
                bathrooms: $this->integer('bathrooms'),
                surface: $entier('surface'),
                price: $this->integer('price'),
                minNights: $this->integer('min_nights'),
                maxNights: $entier('max_nights'),
                checkInFrom: $this->string('check_in_from')->toString(),
                checkOutBefore: $this->string('check_out_before')->toString(),
                petsAllowed: $this->boolean('pets_allowed'),
                smokingAllowed: $this->boolean('smoking_allowed'),
                eventsAllowed: $this->boolean('events_allowed'),
                featured: $this->boolean('featured'),
            ),
            equipements: array_map(
                fn (array $c) => new AmenityChoiceDto((int) $c['id'], (bool) ($c['highlight'] ?? false)),
                $this->validated('amenities', []),
            ),
            categories: array_map('intval', $this->validated('categories', [])),
        );
    }
}
