<?php

namespace App\Http\Requests;

use App\Rules\TelephoneValide;
use App\Support\Telephone;
use Illuminate\Foundation\Http\FormRequest;

/**
 * La fiche du propriétaire, après le code.
 *
 * **Deux choses, et chacune a une raison d'être là.** Le nom, parce que les
 * voyageurs le verront ; le numéro WhatsApp, parce que c'est par là que Vayla
 * appelle pour vérifier — un compte propriétaire injoignable ne dépassera
 * jamais le niveau 1.
 *
 * **Pas de mot de passe** : la connexion se fait par le code reçu à l'adresse,
 * comme côté voyageur. Il n'y a donc rien à inventer, rien à retenir, et rien
 * à récupérer le jour où on l'oublie.
 *
 * **Le numéro est normalisé avant la validation**, jamais après. `unique`
 * compare des chaînes : la saisie brute « +261 34 00 000 01 » ne ressemble pas
 * au « +261340000001 » stocké, et la règle laissait passer un **second compte
 * sur le même numéro écrit autrement** — deux comptes indiscernables au
 * téléphone, dont un seul recevrait le lien d'accès.
 */
class OwnerProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'phone' => ['required', 'string', 'max:40', TelephoneValide::mobile(), 'unique:owners,phone'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($numero = Telephone::depuis($this->input('phone'))) {
            $this->merge(['phone' => $numero->e164()]);
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Votre nom, celui que verront les voyageurs.',
            'phone.required' => 'Votre numéro WhatsApp : c’est par là que Vayla vous joint.',
            'phone.unique' => 'Un compte existe déjà avec ce numéro. Connectez-vous.',
        ];
    }
}
