<?php

namespace App\Enums;

/** Les onglets de la photothèque. La requête de chacun vit dans `PhotoRepository`. */
enum PhotoLibraryTab: string
{
    case Lieux = 'lieux';
    case Televersees = 'televersees';
    case Inutilisees = 'inutilisees';
    case Demonstration = 'demonstration';
    case Proprietaires = 'proprietaires';

    public function label(): string
    {
        return match ($this) {
            self::Lieux => 'Photos de lieux',
            self::Televersees => 'Téléversées',
            self::Inutilisees => 'Inutilisées',
            self::Demonstration => 'Démonstration',
            self::Proprietaires => 'Propriétaires',
        };
    }
}
