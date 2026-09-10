<?php

namespace App\Enums;

/**
 * La nature du logement.
 *
 * Enum et non table : un voyageur qui filtre « bungalow » doit trouver la
 * même chose dans six mois. Une liste éditable finirait par contenir
 * « bungalow », « Bungalow » et « bungalow pieds dans l'eau ».
 */
enum PropertyType: string
{
    case Villa = 'villa';
    case Maison = 'maison';
    case Appartement = 'appartement';
    case Studio = 'studio';
    case Bungalow = 'bungalow';
    case Lodge = 'lodge';
    case Chambre = 'chambre';

    public function label(): string
    {
        return match ($this) {
            self::Villa => 'Villa',
            self::Maison => 'Maison',
            self::Appartement => 'Appartement',
            self::Studio => 'Studio',
            self::Bungalow => 'Bungalow',
            self::Lodge => 'Lodge',
            self::Chambre => 'Chambre chez l\'habitant',
        };
    }

    /** Le logement est-il loué en entier ? */
    public function isWholePlace(): bool
    {
        return $this !== self::Chambre;
    }

    /** @return array<int, self> */
    public static function ordered(): array
    {
        return self::cases();
    }
}
