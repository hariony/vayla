<?php

namespace App\Enums;

use App\Models\Photo;

/**
 * D'où vient une photo — et donc **ce qu'on a le droit d'en faire**.
 *
 * | Provenance    | Légende | Auteur, source | Licence | Supprimer            |
 * |---------------|---------|----------------|---------|----------------------|
 * | Commons       | oui     | oui            | non     | jamais (versionnée)  |
 * | Équipe        | oui     | oui            | oui     | si elle ne sert pas  |
 * | Propriétaire  | oui     | —              | —       | depuis son annonce   |
 * | Démonstration | non     | non            | non     | disparaît avec elle  |
 * | Image générée | non     | non            | non     | disparaît avec elle  |
 *
 * Les règles vivent ici, sur l'enum, comme celles de `TrustLevel` : l'écran,
 * le service et les tests les lisent au même endroit.
 */
enum PhotoProvenance: string
{
    case Commons = 'commons';
    case Equipe = 'equipe';
    case Proprietaire = 'proprietaire';
    case Demonstration = 'demonstration';
    case Generee = 'generee';

    public static function de(Photo $photo): self
    {
        return match (true) {
            $photo->folder === 'annonces' => self::Proprietaire,
            $photo->folder === 'destinations' => self::Equipe,
            (bool) $photo->is_ai || str_starts_with($photo->key, 'ia-') => self::Generee,
            str_starts_with($photo->key, 'an-') => self::Demonstration,
            default => self::Commons,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Commons => 'Wikimedia Commons',
            self::Equipe => 'Téléversée par l’équipe',
            self::Proprietaire => 'Photo de propriétaire',
            self::Demonstration => 'Annonce de démonstration',
            self::Generee => 'Image générée',
        };
    }

    /** La légende est aussi le texte lu aux malvoyants : elle se corrige partout où elle compte. */
    public function legendeModifiable(): bool
    {
        return in_array($this, [self::Commons, self::Equipe, self::Proprietaire], true);
    }

    public function creditModifiable(): bool
    {
        return in_array($this, [self::Commons, self::Equipe], true);
    }

    /** Celle d'une photo de Commons est celle que l'auteur a choisie : elle ne se remplace pas. */
    public function licenceModifiable(): bool
    {
        return $this === self::Equipe;
    }

    /**
     * Pourquoi la photo ne se supprime pas **par nature** — ou `null` si seule
     * la question de ses usages reste à trancher.
     */
    public function pourquoiPasSupprimer(): ?string
    {
        return match ($this) {
            self::Commons => 'Les photographies de Wikimedia Commons ne se suppriment pas d’ici : elles sont versionnées avec leur crédit, dans le catalogue du site.',
            self::Proprietaire => 'Cette photo appartient à l’annonce d’un propriétaire : elle se retire depuis l’annonce.',
            self::Demonstration, self::Generee => 'Elle illustre les annonces de démonstration, et disparaîtra avec elles.',
            self::Equipe => null,
        };
    }
}
