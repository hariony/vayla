<?php

namespace App\Enums;

/** Les périodes des statistiques, en mois. Une autre valeur retombe sur douze. */
enum StatsPeriod: int
{
    case Six = 6;
    case Douze = 12;
    case VingtQuatre = 24;

    public static function depuis(int $mois): self
    {
        return self::tryFrom($mois) ?? self::Douze;
    }

    /** @return list<int> */
    public static function choix(): array
    {
        return array_map(fn (self $p) => $p->value, self::cases());
    }
}
