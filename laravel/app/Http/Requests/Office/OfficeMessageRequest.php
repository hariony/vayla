<?php

namespace App\Http\Requests\Office;

use Illuminate\Foundation\Http\FormRequest;

/** Un mot de Vayla dans le fil d'une réservation. Mêmes bornes que les deux parties. */
class OfficeMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return ['body' => ['required', 'string', 'min:2', 'max:2000']];
    }

    public function messages(): array
    {
        return ['body.required' => 'Le message est vide.'];
    }
}
