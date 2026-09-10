<?php

namespace App\Rules;

use App\Support\Telephone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Un numéro de téléphone utilisable.
 *
 * **Le message dit ce qui ne va pas, et donne un exemple.** « Le champ
 * téléphone est invalide » n'apprend rien à quelqu'un qui vient de taper son
 * numéro comme il le dit ; « 034 00 000 01 ou +261 34 00 000 01 » se recopie.
 *
 * `mobile: true` exige un numéro joignable sur WhatsApp — c'est **le** canal
 * de Vayla, et un fixe malgache ferait échouer l'envoi du lien d'accès sans
 * que personne ne sache pourquoi.
 */
class TelephoneValide implements ValidationRule
{
    public function __construct(
        private bool $mobile = false,
    ) {}

    public static function mobile(): self
    {
        return new self(mobile: true);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $numero = Telephone::depuis(is_string($value) ? $value : null);

        if (! $numero) {
            $fail('Ce numéro n’est pas utilisable. Exemple : 034 00 000 01, ou +261 34 00 000 01.');

            return;
        }

        if ($this->mobile && ! $numero->estMobile()) {
            $fail('Il faut un numéro mobile : c’est par WhatsApp que Vayla vous joint. '
                .'Les numéros commençant par 020 sont des lignes fixes.');
        }
    }
}
