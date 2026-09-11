<?php

namespace App\Http\Requests\Office;

use App\Contracts\Repositories\OfficeAmenityRepositoryInterface;
use App\DTOs\Content\AmenityDto;
use App\Enums\AmenityGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Un équipement. **Le pictogramme se choisit parmi ceux qui existent** : un
 * nom inventé n'a pas de tracé, et retomberait sur le point neutre. La rubrique
 * est un enum — son sens ne glisse pas sous les annonces déjà remplies.
 */
class OfficeAmenityRequest extends FormRequest
{
    /** `rules()` est appelée par le conteneur : le repository s'y injecte. */
    public function rules(OfficeAmenityRepositoryInterface $equipements): array
    {
        $icones = $equipements->icones();

        return [
            'label' => ['required', 'string', 'min:2', 'max:60'],
            'group' => ['required', Rule::enum(AmenityGroup::class)],
            'icon' => ['required', Rule::in($icones)],
            'filterable' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['filterable' => $this->boolean('filterable')]);
    }

    public function toDto(): AmenityDto
    {
        return new AmenityDto(
            label: trim($this->string('label')->toString()),
            group: $this->enum('group', AmenityGroup::class),
            icon: $this->string('icon')->toString(),
            filterable: $this->boolean('filterable'),
        );
    }
}
