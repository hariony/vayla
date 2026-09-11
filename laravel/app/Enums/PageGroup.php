<?php

namespace App\Enums;

use App\Data\OptionData;

/** Les colonnes du pied de page où une page éditoriale peut se ranger, dans l'ordre. */
enum PageGroup: string
{
    case Voyageurs = 'voyageurs';
    case Proprietaires = 'proprietaires';
    case Vayla = 'vayla';
    case Legal = 'legal';

    public function label(): string
    {
        return match ($this) {
            self::Voyageurs => 'Voyageurs',
            self::Proprietaires => 'Propriétaires',
            self::Vayla => 'Vayla',
            self::Legal => 'Informations légales',
        };
    }

    /** @return array<string, string> clé → libellé, dans l'ordre du pied de page */
    public static function libelles(): array
    {
        return array_combine(
            array_map(fn (self $g) => $g->value, self::cases()),
            array_map(fn (self $g) => $g->label(), self::cases()),
        );
    }

    /** @return list<OptionData> */
    public static function options(): array
    {
        return array_map(fn (self $g) => new OptionData($g->value, $g->label()), self::cases());
    }
}
