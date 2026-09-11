<?php

namespace App\Enums;

/**
 * Les pictogrammes du rail de catégories (`Support/categoryIcons.js`). Mêmes
 * clés que le composant — un test les compare, sinon une catégorie choisirait
 * un dessin qui n'existe pas et retomberait sur l'étincelle.
 */
enum CategoryIcon: string
{
    case Etincelle = 'sparkle';
    case Vague = 'wave';
    case Goutte = 'drop';
    case Sommet = 'peak';
    case Feuille = 'leaf';
    case Ville = 'city';
    case Groupe = 'group';
    case Coche = 'check';

    public function label(): string
    {
        return match ($this) {
            self::Etincelle => 'Étincelle',
            self::Vague => 'Vague',
            self::Goutte => 'Goutte',
            self::Sommet => 'Sommet',
            self::Feuille => 'Feuille',
            self::Ville => 'Ville',
            self::Groupe => 'Groupe',
            self::Coche => 'Coche',
        };
    }
}
