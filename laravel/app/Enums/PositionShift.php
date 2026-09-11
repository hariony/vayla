<?php

namespace App\Enums;

/** Déplacer une ligne d'un cran dans une liste ordonnée : le rail, une rubrique d'équipements. */
enum PositionShift: string
{
    case Haut = 'haut';
    case Bas = 'bas';
}
