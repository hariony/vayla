<?php

namespace App\Http\Requests\Office;

use Illuminate\Foundation\Http\FormRequest;

/** Un nouveau membre de l'équipe : un nom, une adresse où recevoir ses codes. */
class OfficeMemberRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:admins,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Cette adresse fait déjà partie de l’équipe.',
            'name.required' => 'Le nom, pour que le journal dise qui a fait quoi.',
        ];
    }
}
