<?php

namespace App\Http\Requests\Office;

use Illuminate\Foundation\Http\FormRequest;

/** Clore une demande : une note pour l'équipe — ce qui a été proposé, ou pourquoi rien. */
class CloseStayRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return ['note' => ['required', 'string', 'min:5', 'max:1000']];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'Une note pour l’équipe : ce qui a été proposé, ou pourquoi rien.',
            'note.min' => 'Cinq caractères au moins.',
        ];
    }

    public function note(): string
    {
        return trim($this->string('note')->toString());
    }
}
