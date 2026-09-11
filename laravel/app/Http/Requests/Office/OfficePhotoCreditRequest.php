<?php

namespace App\Http\Requests\Office;

use App\DTOs\Photos\UpdatePhotoCreditDto;
use App\Enums\PhotoLicence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * La correction d'un crédit. Les bornes sont celles du téléversement ; ce que
 * la provenance permet de toucher, c'est `PhotoCreditEditor` qui le décide —
 * un champ interdit est ignoré, pas refusé.
 *
 * L'auteur, **s'il est envoyé**, ne peut pas être vide : il est crédité au pied
 * de chaque page. La page d'origine, elle, peut être effacée.
 */
class OfficePhotoCreditRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'caption' => ['required', 'string', 'min:4', 'max:160'],
            'author' => ['sometimes', 'required', 'string', 'min:2', 'max:120'],
            'licence' => ['sometimes', 'nullable', Rule::enum(PhotoLicence::class)],
            'source_url' => ['sometimes', 'nullable', 'url:https,http', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'caption.required' => 'Dites ce que montre la photo, et où : c’est aussi le texte lu aux malvoyants.',
            'caption.min' => 'Une légende de quatre caractères au moins : ce que montre la photo, et où.',
            'author.required' => 'Le nom de l’auteur ne peut pas être vide : il est crédité au pied de chaque page.',
            'source_url.url' => 'L’adresse de la page d’origine doit commencer par https://.',
        ];
    }

    public function toDto(): UpdatePhotoCreditDto
    {
        return new UpdatePhotoCreditDto(
            caption: trim($this->string('caption')->toString()),
            author: $this->has('author') ? trim($this->string('author')->toString()) : null,
            licence: $this->filled('licence') ? $this->enum('licence', PhotoLicence::class) : null,
            sourceUrlFournie: $this->has('source_url'),
            sourceUrl: $this->filled('source_url') ? trim($this->string('source_url')->toString()) : null,
        );
    }
}
