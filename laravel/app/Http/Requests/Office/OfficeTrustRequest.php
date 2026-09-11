<?php

namespace App\Http\Requests\Office;

use App\Enums\TrustLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Le niveau de confiance à attribuer. Les règles de fond sont dans `ModerationService`. */
class OfficeTrustRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'level' => ['required', 'integer', Rule::enum(TrustLevel::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
