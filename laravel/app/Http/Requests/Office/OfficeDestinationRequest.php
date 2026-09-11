<?php

namespace App\Http\Requests\Office;

use App\Enums\ClimateZone;
use App\Services\Office\OfficeContentReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Une destination. **« Y aller » est de la donnée, pas de la prose** : sept
 * champs typés plutôt qu'un paragraphe, pour que « à moins de quatre heures de
 * Tana » puisse un jour devenir un filtre. `road_hours` reste une chaîne,
 * parce que la vérité est une fourchette — « 3 à 4 h ».
 *
 * **Les photos ne passent pas par ici** : elles ont leur galerie, et leurs
 * propres gestes (`OfficeContentService::ajouterDeLaPhototheque()` et
 * suivants), qui n'acceptent que de vraies photographies de lieux.
 */
class OfficeDestinationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'region' => ['required', 'string', 'max:60'],
            'tagline' => ['required', 'string', 'min:8', 'max:120'],
            'climate_zone' => ['required', Rule::enum(ClimateZone::class)],
            'scene' => ['required', Rule::in(array_keys(OfficeContentReadService::SCENES))],
            'featured' => ['boolean'],
            'airport_code' => ['nullable', 'string', 'size:3', 'alpha'],
            'airport_name' => ['nullable', 'string', 'max:60'],
            'flight_from_tana' => ['nullable', 'string', 'max:30'],
            'road_route' => ['nullable', 'string', 'max:120'],
            'road_km' => ['nullable', 'integer', 'min:1', 'max:3000'],
            'road_hours' => ['nullable', 'string', 'max:60'],
            'road_note' => ['nullable', 'string', 'max:200'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'featured' => $this->boolean('featured'),
            'airport_code' => $this->filled('airport_code') ? mb_strtoupper(trim((string) $this->input('airport_code'))) : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'airport_code.size' => 'Le code d’aéroport fait trois lettres (NOS, TNR, SMS…).',
        ];
    }
}
