<?php

namespace App\Http\Requests\Office;

use App\Services\Office\OfficeContentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * La correction d'un crédit. Les bornes sont celles du téléversement ; ce que
 * la provenance permet de toucher, c'est `OfficePhotoLibraryService` qui le
 * décide — un champ interdit est ignoré, pas refusé.
 */
class OfficePhotoCreditRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'caption' => ['required', 'string', 'min:4', 'max:160'],
            'author' => ['sometimes', 'nullable', 'string', 'max:120'],
            'licence' => ['sometimes', 'nullable', Rule::in(array_keys(OfficeContentService::LICENCES))],
            'source_url' => ['sometimes', 'nullable', 'url:https,http', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'caption.required' => 'Dites ce que montre la photo, et où : c’est aussi le texte lu aux malvoyants.',
            'caption.min' => 'Une légende de quatre caractères au moins : ce que montre la photo, et où.',
            'source_url.url' => 'L’adresse de la page d’origine doit commencer par https://.',
        ];
    }
}
