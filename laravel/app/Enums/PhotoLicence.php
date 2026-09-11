<?php

namespace App\Enums;

/**
 * Les licences qu'une photo **téléversée par l'équipe** peut porter, et la
 * page qui les définit. « Tous droits réservés » n'a pas de page : c'est une
 * photo de l'équipe, ou d'un photographe qui l'a cédée.
 *
 * Un enum et non une liste éditable, comme `TrustLevel` : le sens d'une
 * licence ne doit pas glisser sous les photos déjà créditées. Les photos de
 * Commons gardent la leur, souvent en 2.0 ou 3.0, que cette liste ne porte
 * pas — et c'est pourquoi elle ne s'applique pas à elles.
 */
enum PhotoLicence: string
{
    case Vayla = 'vayla';
    case Accord = 'accord';
    case CcBy = 'cc-by';
    case CcBySa = 'cc-by-sa';
    case DomainePublic = 'domaine-public';

    public function label(): string
    {
        return match ($this) {
            self::Vayla => 'Photo Vayla — tous droits réservés',
            self::Accord => 'Publiée avec l’accord de l’auteur',
            self::CcBy => 'CC BY 4.0',
            self::CcBySa => 'CC BY-SA 4.0',
            self::DomainePublic => 'Domaine public',
        };
    }

    public function url(): ?string
    {
        return match ($this) {
            self::CcBy => 'https://creativecommons.org/licenses/by/4.0/deed.fr',
            self::CcBySa => 'https://creativecommons.org/licenses/by-sa/4.0/deed.fr',
            default => null,
        };
    }

    /** La licence dont le libellé est stocké sur la photo, s'il vient de cette liste. */
    public static function depuisLibelle(?string $libelle): ?self
    {
        foreach (self::cases() as $licence) {
            if ($licence->label() === $libelle) {
                return $licence;
            }
        }

        return null;
    }

    /** @return array<int, string> */
    public static function valeurs(): array
    {
        return array_column(self::cases(), 'value');
    }
}
