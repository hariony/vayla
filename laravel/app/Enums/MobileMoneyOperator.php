<?php

namespace App\Enums;

/**
 * Les trois opérateurs de mobile money qui existent à Madagascar, et rien
 * d'autre. **Une liste, pas un champ libre** : un opérateur écrit de trois
 * façons est un opérateur qu'on ne peut plus regrouper.
 *
 * **Les valeurs sont les libellés eux-mêmes, pas des clés.** La colonne porte
 * déjà « MVola » et « Orange Money » — ceux que le seeder écrit et que la
 * facture affiche telle quelle. Des clés auraient obligé à une table de
 * correspondance et à une migration des lignes existantes, pour trois valeurs
 * qui ne changeront pas.
 */
enum MobileMoneyOperator: string
{
    case MVola = 'MVola';
    case OrangeMoney = 'Orange Money';
    case AirtelMoney = 'Airtel Money';

    /** @return list<string> */
    public static function valeurs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
