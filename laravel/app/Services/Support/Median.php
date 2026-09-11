<?php

namespace App\Services\Support;

use Illuminate\Support\Collection;

/** La médiane : la moitié des valeurs au-dessous, l'autre au-dessus. `null` sans valeur. */
final class Median
{
    /** @param  Collection<int, int|float>  $valeurs */
    public static function de(Collection $valeurs): ?float
    {
        if ($valeurs->isEmpty()) {
            return null;
        }

        $tries = $valeurs->sort()->values();
        $milieu = intdiv($tries->count(), 2);

        return $tries->count() % 2
            ? (float) $tries[$milieu]
            : ($tries[$milieu - 1] + $tries[$milieu]) / 2;
    }
}
