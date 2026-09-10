<?php

namespace App\Enums;

/**
 * Les familles d'équipements, dans l'ordre où elles s'affichent.
 *
 * C'est un enum et non une table pour la même raison que l'échelle de
 * confiance : ce sont des rubriques de lecture, pas de la donnée. Les rendre
 * éditables ferait glisser le sens d'une rubrique sous les annonces déjà
 * remplies. Les équipements eux-mêmes, eux, sont bien une table : la liste
 * s'allongera au fil des logements réels.
 *
 * `Energie` n'existe pas chez les plateformes du Nord. À Madagascar, le
 * délestage et l'eau décident du séjour : c'est la rubrique qui sépare une
 * annonce honnête d'une belle photo.
 */
enum AmenityGroup: string
{
    case Essentiels = 'essentiels';
    case Couchage = 'couchage';
    case Cuisine = 'cuisine';
    case SalleEau = 'salle_eau';
    case Exterieur = 'exterieur';
    case Energie = 'energie';
    case Securite = 'securite';
    case Services = 'services';
    case Loisirs = 'loisirs';
    case Acces = 'acces';
    case Accessibilite = 'accessibilite';

    public function label(): string
    {
        return match ($this) {
            self::Essentiels => 'Essentiels',
            self::Couchage => 'Chambre et couchage',
            self::Cuisine => 'Cuisine',
            self::SalleEau => 'Salle d\'eau',
            self::Exterieur => 'Extérieur et piscine',
            self::Energie => 'Énergie et eau',
            self::Securite => 'Sécurité',
            self::Services => 'Services',
            self::Loisirs => 'Loisirs',
            self::Acces => 'Accès et stationnement',
            self::Accessibilite => 'Accessibilité',
        };
    }

    /**
     * Une note de rubrique, quand la liste seule ne suffit pas à comprendre
     * ce qui est en jeu. La plupart n'en ont pas besoin.
     */
    public function note(): ?string
    {
        return match ($this) {
            self::Energie => 'Le délestage et l\'eau courante ne vont pas de soi. Ce que le logement possède est écrit ici, pas sous-entendu.',
            self::Accessibilite => 'Déclaré par le propriétaire, contrôlé à la visite à partir du niveau 3.',
            default => null,
        };
    }

    /** Rang d'affichage : l'ordre de déclaration des cas fait foi. */
    public function position(): int
    {
        return array_search($this, self::cases(), true);
    }

    /** @return array<int, self> */
    public static function ordered(): array
    {
        return self::cases();
    }
}
