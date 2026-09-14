<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * La source d'un lien d'inscription propriétaire (`?source=facebook-nosybe`).
 *
 * **Nettoyée, jamais refusée.** C'est une page en GET atteinte par une
 * publicité : un paramètre abîmé par une application qui réécrit les liens ne
 * doit pas fermer la porte à quelqu'un qui vient s'inscrire. Ce qui n'a pas la
 * forme d'une source — minuscules, chiffres et tirets, soixante signes au
 * plus — est simplement oublié.
 */
class SignupSourceRequest extends FormRequest
{
    public function rules(): array
    {
        return ['source' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/']];
    }

    protected function prepareForValidation(): void
    {
        $source = $this->query('source');
        $source = is_string($source) ? mb_strtolower(trim($source)) : null;

        $this->merge([
            'source' => $source !== null && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $source) && mb_strlen($source) <= 60
                ? $source
                : null,
        ]);
    }

    public function source(): ?string
    {
        return $this->input('source');
    }
}
