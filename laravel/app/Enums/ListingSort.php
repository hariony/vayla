<?php

namespace App\Enums;

/**
 * Les tris proposés sur le catalogue.
 *
 * Enum plutôt que chaîne libre : le tri arrive par l'URL, donc de
 * l'extérieur. Un enum ferme la porte à `?sort=price;DROP` et donne au
 * front la liste exacte des choix, sans la recopier.
 *
 * `Confiance` est le défaut, et ce n'est pas neutre : sur un site dont la
 * promesse est la vérification, l'ordre naturel des résultats est le niveau
 * de confiance, pas le prix.
 */
enum ListingSort: string
{
    case Confiance = 'confiance';
    case PrixCroissant = 'prix-asc';
    case PrixDecroissant = 'prix-desc';
    case Capacite = 'capacite';

    public function label(): string
    {
        return match ($this) {
            self::Confiance => 'Vérification d\'abord',
            self::PrixCroissant => 'Prix croissant',
            self::PrixDecroissant => 'Prix décroissant',
            self::Capacite => 'Capacité d\'accueil',
        };
    }

    /** @return array<int, self> */
    public static function ordered(): array
    {
        return self::cases();
    }

    public static function defaut(): self
    {
        return self::Confiance;
    }
}
