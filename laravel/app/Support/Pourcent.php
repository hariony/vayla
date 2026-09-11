<?php

namespace App\Support;

/** Un taux en pourcentage à la française : 0.05 → « 5 % », 0.075 → « 7,5 % », avec l'espace insécable. */
final class Pourcent
{
    public static function de(float $taux): string
    {
        return rtrim(rtrim(number_format($taux * 100, 1, ',', ''), '0'), ',')."\u{00A0}%";
    }
}
