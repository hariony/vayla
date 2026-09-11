<?php

namespace App\Enums;

/**
 * Les catégories du rail qui **ne sont pas des étiquettes** : « Tout » (le rail
 * sans filtre) et « Séjour confirmé », qui se déduit du niveau 4. Elles se
 * renomment et se déplacent, mais ne se posent sur aucune annonce, ne se
 * suppriment pas — et « Séjour confirmé » ne se vend pas.
 */
enum StructuralCategory: string
{
    case Tout = 'all';
    case Verifie = 'verifie';

    /** @return array<int, string> */
    public static function cles(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function est(string $cle): bool
    {
        return self::tryFrom($cle) !== null;
    }
}
