<?php

namespace App\Http\Requests\Office;

use App\Enums\AmenityGroup;
use App\Models\Amenity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Un équipement. **Le pictogramme se choisit parmi ceux qui existent** : un
 * nom inventé n'a pas de tracé, et retomberait sur le point neutre. La rubrique
 * est un enum — son sens ne glisse pas sous les annonces déjà remplies.
 */
class OfficeAmenityRequest extends FormRequest
{
    public function rules(): array
    {
        $icones = Amenity::query()->distinct()->pluck('icon')->push('dot')->unique()->all();

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
}
