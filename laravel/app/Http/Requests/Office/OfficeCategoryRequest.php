<?php

namespace App\Http\Requests\Office;

use App\Services\Office\OfficeContentReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Une catégorie du rail « Les envies du moment ». Son titre est **éditorial,
 * jamais statistique** ; et une place achetée (`sponsored`) est signalée sur
 * le site, en toutes lettres.
 */
class OfficeCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'min:2', 'max:40'],
            'icon' => ['required', Rule::in(array_keys(OfficeContentReadService::ICONES_CATEGORIES))],
            'sponsored' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['sponsored' => $this->boolean('sponsored')]);
    }
}
