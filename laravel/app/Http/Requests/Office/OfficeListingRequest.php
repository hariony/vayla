<?php

namespace App\Http\Requests\Office;

use App\DTOs\Listings\ListingContentDto;
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
        return new ListingContentDto(
            fiche: $this->ficheDto(featured: $this->boolean('featured')),
            equipements: $this->equipementsDto(),
            categories: array_map('intval', $this->validated('categories', [])),
        );
    }
}
