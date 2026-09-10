<?php

namespace App\Enums;

/**
 * Pourquoi un logement n'est pas libre sur une période.
 *
 * **Une liste, pas un champ libre.** Le propriétaire déclare ses périodes
 * depuis son téléphone, souvent modeste, et beaucoup n'ont jamais rempli de
 * formulaire en ligne : cinq boutons se cochent en une seconde, une zone de
 * texte se remplit mal ou pas du tout. Le motif n'est de toute façon jamais
 * une donnée de décision — il n'apparaît nulle part côté voyageur, où seule
 * compte l'occupation — c'est une note que le propriétaire s'écrit à
 * lui-même pour reconnaître sa période trois semaines plus tard.
 *
 * **Enum et non table** : le sens d'un motif ne doit pas glisser sous les
 * périodes déjà déclarées, et cinq valeurs couvrent ce qui ferme réellement
 * un logement à Madagascar. « Loué en direct » vient en premier parce que
 * c'est le cas majoritaire : Vayla arrive dans la vie de propriétaires qui
 * louaient déjà par WhatsApp et par le bouche-à-oreille, et cette
 * double-vie durera.
 */
enum BlockReason: string
{
    case LoueDirect = 'loue_direct';
    case Occupe = 'occupe';
    case Travaux = 'travaux';
    case Ferme = 'ferme';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::LoueDirect => 'Loué en direct',
            self::Occupe => "Je l'occupe",
            self::Travaux => 'Travaux ou entretien',
            self::Ferme => 'Fermé cette saison',
            self::Autre => 'Autre raison',
        };
    }

    /** Les valeurs acceptées par le formulaire. */
    public static function valeurs(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Le vocabulaire publié au front : une seule source pour les libellés. */
    public static function options(): array
    {
        return array_map(
            fn (self $r) => ['value' => $r->value, 'label' => $r->label()],
            self::cases(),
        );
    }
}
