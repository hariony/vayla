<?php

namespace App\Http\Requests\Office;

use App\Services\Content\PageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Une page éditoriale. Les règles d'adresse — libre, pas celle d'un écran,
 * figée après publication — sont dans `PageService` : elles lisent le routeur.
 *
 * La description pour les moteurs est bornée à 160 caractères : c'est ce
 * qu'un moteur affiche avant de couper, au milieu d'un mot.
 */
class OfficePageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:120'],
            'slug' => ['nullable', 'string', 'max:80'],
            'lede' => ['nullable', 'string', 'max:300'],
            'body' => ['nullable', 'string', 'max:60000'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'footer_group' => ['nullable', Rule::in(array_keys(PageService::GROUPES))],
            'internal_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Donnez un titre à la page.',
            'seo_description.max' => 'Cent soixante caractères au plus : au-delà, les moteurs coupent au milieu d’un mot.',
        ];
    }
}
