<?php

namespace App\Http\Requests\Office;

use App\DTOs\Content\CategoryDto;
use App\Enums\CategoryIcon;
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
            'icon' => ['required', Rule::enum(CategoryIcon::class)],
            'sponsored' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['sponsored' => $this->boolean('sponsored')]);
    }

    public function toDto(): CategoryDto
    {
        return new CategoryDto(
            label: trim($this->string('label')->toString()),
            icon: $this->enum('icon', CategoryIcon::class),
            sponsored: $this->boolean('sponsored'),
        );
    }
}
