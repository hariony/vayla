<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Le code à six chiffres. **Pas de refus de forme ici** : un code vide ou mal
 * tapé reçoit la même réponse qu'un code faux — « incorrect ou expiré » — et
 * c'est le service des codes qui compte les essais.
 */
class VerificationCodeRequest extends FormRequest
{
    public function rules(): array
    {
        return ['code' => ['nullable', 'string', 'max:20']];
    }

    public function code(): string
    {
        return trim($this->string('code')->toString());
    }
}
