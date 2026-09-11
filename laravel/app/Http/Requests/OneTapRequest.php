<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Le jeton que Google One Tap poste. **Pas de refus de forme ici** : un jeton
 * absent ou mal formé reçoit la même réponse qu'un jeton refusé par Google —
 * c'est `GoogleIdToken` qui en juge.
 */
class OneTapRequest extends FormRequest
{
    public function rules(): array
    {
        return ['credential' => ['nullable', 'string', 'max:8192']];
    }

    public function credential(): string
    {
        return $this->string('credential')->toString();
    }
}
