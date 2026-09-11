<?php

namespace App\Enums;

/**
 * Les illustrations de repli d'une destination (`SceneArt`), affichées faute
 * de photo. Les clés sont celles du composant ; un test vérifie qu'elles y
 * existent toutes.
 */
enum DestinationScene: string
{
    case Lagon = 'lagoon';
    case Plage = 'beach';
    case Couchant = 'sunset';
    case Lac = 'lake';
    case HautesTerres = 'highland';
    case Foret = 'forest';

    public function label(): string
    {
        return match ($this) {
            self::Lagon => 'Lagon',
            self::Plage => 'Plage',
            self::Couchant => 'Couchant',
            self::Lac => 'Lac',
            self::HautesTerres => 'Hautes terres',
            self::Foret => 'Forêt',
        };
    }
}
