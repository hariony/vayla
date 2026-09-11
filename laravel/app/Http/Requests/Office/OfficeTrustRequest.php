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

    public function niveau(): TrustLevel
    {
        return TrustLevel::from($this->integer('level'));
    }

    public function note(): ?string
    {
        return $this->filled('note') ? trim($this->string('note')->toString()) : null;
    }
}
