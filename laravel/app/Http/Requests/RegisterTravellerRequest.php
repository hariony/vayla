<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * La porte du voyageur : une adresse e-mail, et rien d'autre.
 *
 * **Un seul champ, et il sert aux deux gestes.** S'inscrire et se connecter
 * posent la même question — « quelle est votre adresse ? » — et c'est le code
 * reçu qui décide de la suite : le compte existe, on entre ; il n'existe pas,
 * il s'ouvre. Demander à l'utilisateur de choisir *avant* entre « créer » et
 * « se connecter » lui fait trancher une question dont il n'a pas la réponse :
 * beaucoup ne savent plus s'ils ont déjà un compte, et se trompent de porte.
 *
 * **Donc pas de règle `unique`.** Elle refusait une adresse déjà connue, ce
 * qui n'a plus de sens ici, et surtout elle **disait** qu'un compte existait —
 * un inconnu pouvait tester des adresses une par une pour savoir lesquelles
 * sont sur Vayla. Le code, lui, ne révèle rien : il part dans tous les cas, et
 * seul celui qui relève la boîte apprend quelque chose.
 */
class RegisterTravellerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:190'],
        ];
    }

    /**
     * L'adresse est mise en minuscules avant la validation : sinon
     * `Claire@Example.com` et `claire@example.com` ouvriraient deux comptes
     * pour une seule boîte.
     */
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
}
