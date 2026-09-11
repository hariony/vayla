<?php

namespace App\Http\Requests\Office;

use App\DTOs\Content\PageDto;
use App\Enums\PageGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Une page éditoriale. Les règles d'adresse — libre, pas celle d'un écran,
 * figée après publication — sont dans `PageAddresses` et `PageEditor` : elles
 * lisent le routeur et l'état de la page.
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
            'footer_group' => ['nullable', Rule::enum(PageGroup::class)],
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

    public function toDto(): PageDto
    {
        $texte = fn (string $champ) => $this->filled($champ) ? (trim($this->string($champ)->toString()) ?: null) : null;

        return new PageDto(
            title: trim($this->string('title')->toString()),
            slug: $this->filled('slug') ? $this->string('slug')->toString() : null,
            lede: $texte('lede'),
            body: $this->filled('body') ? $this->string('body')->toString() : null,
            seoDescription: $texte('seo_description'),
            groupe: $this->enum('footer_group', PageGroup::class),
            internalNote: $texte('internal_note'),
        );
    }
}
