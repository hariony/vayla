<?php

namespace App\Http\Requests\Office;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Choisir son mot de passe.
 *
 * **L'actuel est toujours demandé**, même au premier choix : c'est la preuve
 * que la personne devant l'écran est celle qui vient d'entrer, et pas celle
 * qui s'assoit devant une session restée ouverte.
 *
 * **Douze caractères, et pas de règle de forme** — pas de majuscule imposée ni
 * de chiffre obligatoire, qui produisent des mots de passe notés à côté de
 * l'écran. On refuse en revanche ceux qui figurent dans des fuites connues :
 * c'est la seule contrainte qui protège vraiment. Plus long que les huit
 * caractères demandés autrefois aux propriétaires, parce que ce compte-ci
 * publie des annonces au nom de Vayla.
 */
class OfficePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:admin'],
            'password' => ['required', 'string', 'max:72', 'confirmed', 'different:current_password', Password::min(12)->uncompromised()],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Saisissez votre mot de passe actuel.',
            'current_password.current_password' => 'Ce n’est pas votre mot de passe actuel.',
            'password.required' => 'Choisissez un nouveau mot de passe.',
            'password.confirmed' => 'Les deux saisies ne sont pas identiques.',
            'password.different' => 'Le nouveau doit être différent de l’actuel.',
            'password.min' => 'Douze caractères au moins.',
            'password.uncompromised' => 'Ce mot de passe figure dans des fuites de données connues : choisissez-en un autre.',
        ];
    }

    /** Le nouveau mot de passe, tel que saisi : le cast `hashed` du modèle le hachera. */
    public function nouveauMotDePasse(): string
    {
        return $this->string('password')->toString();
    }
}
