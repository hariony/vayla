<?php

namespace App\Http\Requests;

use App\DTOs\Auth\TravellerAccountDto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Ce qu'un voyageur peut corriger sur son compte.
 *
 * **Trois champs, et pas un de plus.** Vayla ne demande ni mot de passe, ni
 * date de naissance, ni adresse postale : le compte sert à retrouver ses
 * séjours et à ne pas resaisir la même chose à chaque demande, pas à
 * constituer un dossier.
 *
 * **Nom et prénom séparés, parce qu'un champ unique ne se relit pas.**
 * « RAKOTOBE Jean » est une écriture courante ici, « Jean Rakotobe » l'est
 * ailleurs, et rien ne dit laquelle on a sous les yeux — l'écran des
 * réservations en avait fait « Bonjour RAKOTOBE ». Deux champs lèvent
 * l'ambiguïté à la saisie.
 *
 * **Le numéro n'est pas un identifiant, et il n'est pas unique** : côté
 * propriétaire il ouvre un compte, ici il sert à pré-remplir une demande de
 * séjour, et deux personnes d'un même foyer partagent parfaitement une ligne.
 * Ses bornes sont **celles du formulaire de réservation** — c'est le même
 * numéro, il ne peut pas être accepté ici et refusé là.
 *
 * **Tout est facultatif.** Le compte s'ouvre avec une adresse et rien d'autre ;
 * exiger un nom pour enregistrer une correction de numéro serait exiger deux
 * fois ce qui n'a jamais été demandé. Le formulaire de séjour redemandera ce
 * qui manque, au moment où ça sert.
 *
 * **L'adresse n'est pas modifiable ici** : elle est l'identifiant de connexion
 * *et* ce qui rattache les séjours au compte. La changer d'un formulaire
 * détacherait des réservations déjà faites — et donnerait le compte à qui a
 * laissé une session ouverte. Elle se change en écrivant à Vayla.
 */
class TravellerAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'min:2', 'max:60'],
            'last_name' => ['nullable', 'string', 'min:2', 'max:60'],
            'phone' => ['nullable', 'string', 'min:8', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.min' => 'Un prénom d’au moins deux caractères, ou rien du tout.',
            'last_name.min' => 'Un nom d’au moins deux caractères, ou rien du tout.',
            'phone.min' => 'Un numéro joignable, ou rien du tout.',
        ];
    }

    public function toDto(): TravellerAccountDto
    {
        $texte = fn (string $champ) => $this->filled($champ) ? trim($this->string($champ)->toString()) : null;

        return new TravellerAccountDto($texte('first_name'), $texte('last_name'), $texte('phone'));
    }
}
