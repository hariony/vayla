<?php

namespace App\Data\Office;

use App\Support\Telephone;
use Spatie\LaravelData\Data;

/**
 * Un numéro prêt à servir : lisible, à appeler, à écrire. `tel:` et `wa.me`
 * sont les deux gestes de la vérification — l'appel, et le message quand
 * personne ne décroche. Le `+` saute pour `wa.me`, qui ouvre sinon une
 * conversation vide sans dire pourquoi.
 */
final class PhoneData extends Data
{
    public function __construct(
        public readonly string $lisible,
        public readonly string $tel,
        public readonly ?string $whatsapp,
        public readonly ?string $operateur,
    ) {}

    public static function depuis(?string $brut): ?self
    {
        if (! $brut) {
            return null;
        }

        $t = Telephone::depuis($brut);

        return new self(
            lisible: $t?->lisible() ?? $brut,
            tel: 'tel:'.($t?->e164() ?? preg_replace('/[^\d+]/', '', $brut)),
            whatsapp: $t && $t->estMobile() ? 'https://wa.me/'.ltrim($t->e164(), '+') : null,
            operateur: $t?->operateur(),
        );
    }
}
