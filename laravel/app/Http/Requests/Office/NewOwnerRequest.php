<?php

namespace App\Http\Requests\Office;

use App\DTOs\Office\NewOwnerDto;
use App\Rules\TelephoneValide;
use App\Support\Telephone;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Un propriétaire inscrit par l'équipe, pendant un appel.
 *
 * **Le numéro WhatsApp suffit** : c'est par là que part son lien d'accès, et ce
 * lien ouvre son espace sans adresse ni code. L'adresse e-mail est facultative
 * — beaucoup ne relèvent pas de boîte ; il pourra se connecter par code le jour
 * où il en donne une.
 *
 * Normalisé **avant** la validation, pour la même raison qu'à l'inscription :
 * `unique` compare des chaînes, et « 034 00 000 01 » passerait à côté du
 * « +261340000001 » déjà en base.
 */
class NewOwnerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $email = trim((string) $this->input('email'));

        $this->merge([
            'email' => $email === '' ? null : mb_strtolower($email),
            'city' => trim((string) $this->input('city')) ?: null,
        ]);

        if ($numero = Telephone::depuis($this->input('phone'))) {
            $this->merge(['phone' => $numero->e164()]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'phone' => ['required', 'string', 'max:40', TelephoneValide::mobile(), 'unique:owners,phone'],
            'email' => ['nullable', 'email:rfc', 'max:190', 'unique:owners,email'],
            'city' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom, celui que verront les voyageurs.',
            'phone.required' => 'Le numéro WhatsApp : c’est par là que part son lien d’accès.',
            'phone.unique' => 'Ce numéro a déjà un compte. Cherchez-le dans la liste.',
            'email.unique' => 'Cette adresse a déjà un compte. Cherchez-la dans la liste.',
        ];
    }

    public function toDto(): NewOwnerDto
    {
        return new NewOwnerDto(
            trim($this->string('name')->toString()),
            $this->string('phone')->toString(),
            $this->input('email'),
            $this->input('city'),
        );
    }
}
