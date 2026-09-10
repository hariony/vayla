<?php

namespace App\Enums;

/**
 * L'échelle de confiance : le coeur de la promesse Vayla.
 *
 * Quatre niveaux, et un seul endroit où ils sont définis. Toute surface qui
 * nomme un logement (annonce, jauge, API, application mobile) lit cet enum ;
 * aucun libellé de niveau ne doit être réécrit ailleurs.
 *
 * Le niveau 1 n'est PAS une vérification — c'est une déclaration. C'est la
 * raison pour laquelle il porte le gris `--unverified` et non le lagon :
 * le vert ne commence qu'au niveau 2.
 */
enum TrustLevel: int
{
    case Declared = 1;
    case Contact = 2;
    case Visited = 3;
    case Proven = 4;

    /** Clé stable, utilisée par le front et l'API. */
    public function key(): string
    {
        return match ($this) {
            self::Declared => 'declared',
            self::Contact => 'contact',
            self::Visited => 'visited',
            self::Proven => 'proven',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Declared => 'Annonce déclarée',
            self::Contact => 'Contact confirmé',
            self::Visited => 'Logement visité',
            self::Proven => 'Séjour confirmé',
        };
    }

    public function summary(): string
    {
        return match ($this) {
            self::Declared => 'Le propriétaire a créé sa fiche et accepté nos règles.',
            self::Contact => 'Numéro et identité vérifiés, photos contrôlées contre le vol d\'annonce.',
            self::Visited => 'Visite guidée en direct par visio, ou contrôle par notre correspondant local.',
            self::Proven => 'Des voyageurs y ont dormi et ont confirmé que tout correspondait.',
        };
    }

    /**
     * Un logement est « vérifié » à partir du niveau 2 : le niveau 1 est
     * déclaratif. Le rail de catégories, lui, ne montre que le niveau 4.
     */
    public function isVerified(): bool
    {
        return $this->value >= self::Contact->value;
    }

    /** @return array<int, self> */
    public static function ladder(): array
    {
        return self::cases();
    }
}
