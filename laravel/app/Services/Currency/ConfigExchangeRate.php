<?php

namespace App\Services\Currency;

/**
 * Le taux tel qu'il est posé à la main dans la configuration.
 *
 * C'est volontairement le plus bête possible : Vayla **n'encaisse rien**, le
 * voyageur règle en ariary sur place, et l'euro n'est qu'un ordre de
 * grandeur pour quelqu'un qui ne sait pas ce que valent 185 000 Ar. Une
 * précision au centime n'apporterait rien, et un appel réseau à chaque
 * affichage de prix apporterait surtout une panne possible.
 *
 * La date est saisie avec le taux, jamais déduite : « taux du jour » calculé
 * depuis `now()` sur une valeur figée il y a six mois serait exactement le
 * petit mensonge que le reste du produit refuse.
 */
class ConfigExchangeRate implements ExchangeRateProvider
{
    public function ariaryParEuro(): float
    {
        return (float) config('vayla.currency.eur_rate');
    }

    public function releveLe(): string
    {
        return (string) config('vayla.currency.eur_rate_date');
    }
}
