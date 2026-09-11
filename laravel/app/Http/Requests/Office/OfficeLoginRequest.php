<?php

namespace App\Http\Requests\Office;

use Illuminate\Foundation\Http\FormRequest;

/**
 * L'adresse et le mot de passe de connexion au back-office.
 *
 * Aucune règle de forme sur le mot de passe ici : on vérifie un mot de passe,
 * on ne le juge pas. Les bornes sont celles qui empêchent d'envoyer n'importe
 * quoi au hachage — bcrypt ne lit que les 72 premiers octets.
 */
class OfficeLoginRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:190'],
            'password' => ['required', 'string', 'max:72'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Saisissez votre adresse e-mail.',
            'email.email' => 'Cette adresse ne semble pas complète. Exemple : prenom@vayla.mg',
            'password.required' => 'Saisissez votre mot de passe.',
        ];
    }
}
