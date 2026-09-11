<?php

namespace App\Services\Support;

use App\Enums\PositionShift;

/**
 * Échanger une ligne avec sa voisine dans une liste ordonnée. **Pur** : il
 * reçoit l'ordre actuel et rend le nouveau, sans rien écrire — le repository
 * pose les positions, en renumérotant de 0 à n − 1.
 */
final class PositionSwapper
{
    /**
     * @param  array<int, int>  $ordre  les identifiants, dans l'ordre actuel
     * @return array<int, int>|null le nouvel ordre, ou `null` si la ligne est déjà au bord
     */
    public function deplacer(array $ordre, int $id, PositionShift $sens): ?array
    {
        $ordre = array_values($ordre);
        $i = array_search($id, $ordre, true);
        $j = $sens === PositionShift::Haut ? $i - 1 : $i + 1;

        if ($i === false || $j < 0 || $j >= count($ordre)) {
            return null;
        }

        [$ordre[$i], $ordre[$j]] = [$ordre[$j], $ordre[$i]];

        return $ordre;
    }
}
