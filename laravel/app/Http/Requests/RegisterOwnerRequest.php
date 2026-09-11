<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Première étape de l'inscription propriétaire : l'adresse, seule.
 *
 * **Un formulaire de six champs devant quelqu'un qui n'a encore rien reçu de
 * Vayla est un formulaire qu'on quitte.** Une adresse et un bouton, non. Le
 * nom, le numéro WhatsApp et le mot de passe arrivent après le code, quand la
 * personne a déjà fait un geste et vu que le service répond —
 * `OwnerProfileRequest` les valide là.
 *
 * **Pas de règle `unique` ici.** Elle dirait à un inconnu qu'une adresse est
 * déjà celle d'un propriétaire de Vayla, adresse par adresse. L'unicité est
 * tenue à la création, où elle porte sur le numéro — l'identifiant de
 * connexion — et où personne ne la lit.
 */
class RegisterOwnerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:190'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Votre adresse e-mail : c’est là qu’arrive votre code.',
            'email.email' => 'Cette adresse ne ressemble pas à une adresse e-mail.',
        ];
    }

    /** En minuscules, sans espaces : normalisée avant la validation. */
    public function email(): string
    {
        return $this->string('email')->toString();
    }
}
