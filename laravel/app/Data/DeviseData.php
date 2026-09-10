<?php

namespace App\Data;

use App\Services\Currency\ExchangeRateProvider;
use Spatie\LaravelData\Data;

/**
 * Le taux publié aux clients — site et application mobile.
 *
 * **L'ariary est le prix, l'euro est une aide à la lecture.** Tous les
 * montants du dépôt sont et restent des entiers d'ariary : rien n'est stocké
 * en euros, rien n'est calculé en euros. Ce contrat ne transporte donc pas
 * des prix convertis mais **le taux**, et chaque surface convertit ce
 * qu'elle affiche — c'est la seule façon de convertir un total qui n'existe
 * que côté client, comme les nuits × tarif de l'encart de réservation.
 *
 * Conséquence assumée : l'arrondi est une affaire d'affichage, et deux
 * écrans peuvent différer d'un euro sur une somme de lignes arrondies
 * séparément. C'est sans importance pour un ordre de grandeur, et le signe
 * « ≈ » le dit. Ce serait inacceptable sur un montant encaissé — Vayla n'en
 * encaisse aucun.
 */
class DeviseData extends Data
{
    public function __construct(
        public readonly string $code,
        public readonly string $symbole,
        /** Ariary pour un euro. */
        public readonly float $taux,
        /** `AAAA-MM-JJ` — la date du relevé, jamais celle du jour. */
        public readonly string $releveLe,
    ) {}

    public static function depuis(ExchangeRateProvider $source): self
    {
        return new self(
            code: 'EUR',
            symbole: '€',
            taux: $source->ariaryParEuro(),
            releveLe: $source->releveLe(),
        );
    }
}
