<?php

namespace App\Services;

use App\Data\SeasonData;
use App\Data\SeasonMonthData;
use App\Data\SeasonYearMonthData;
use App\Enums\ClimateZone;
use App\Enums\SeasonKind;
use App\Models\Destination;
use Illuminate\Support\Carbon;

/**
 * Ce que vaut chaque mois, à cet endroit-là.
 *
 * C'est ce qui distingue le calendrier de Vayla de tous les autres : ailleurs
 * une date dit « libre » ou « pris ». Ici elle dit aussi **si c'est le bon
 * moment**. Un voyageur qui pose ses dates à Nosy Be en février doit lire
 * « risque cyclonique » sur l'écran, pas le découvrir à l'aéroport.
 *
 * Deux honnêtetés obligatoires, portées jusque dans la charge utile :
 *   — `caveat` dit que ce sont des **tendances de saison**, pas une
 *     prévision. Une plateforme qui laisserait croire à une prévision
 *     mentirait, et Vayla n'a que ça à vendre ;
 *   — les mauvais mois sont écrits comme les bons. C'est « cyclones
 *     possibles » en février qui rend « meilleure période » crédible en août.
 */
class SeasonService
{
    private const MOIS = [
        'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
    ];

    /**
     * Les douze mois de l'horizon, à partir du mois courant.
     *
     * Indexés par `AAAA-MM` : le calendrier navigue par mois et n'a alors
     * aucun calcul à faire pour retrouver la saison qu'il affiche.
     *
     * @return array<string, array<string, mixed>>
     */
    public function forDestination(Destination $destination, int $months = 13): array
    {
        $zone = $destination->climate_zone ?? ClimateZone::HautesTerres;
        $profil = $zone->months();
        $curseur = Carbon::today()->startOfMonth();
        $out = [];

        for ($i = 0; $i < $months; $i++) {
            [$etat, $note] = $profil[$curseur->month - 1];
            $kind = SeasonKind::from($etat);

            $out[$curseur->format('Y-m')] = new SeasonMonthData(
                month: self::MOIS[$curseur->month - 1],
                kind: $kind->value,
                label: $kind->label(),
                icon: $kind->icon(),
                note: $note,
                warning: $kind->isWarning(),
                best: $kind->isBest(),
            );

            $curseur->addMonth();
        }

        return $out;
    }

    /**
     * Les meilleurs mois de l'année sur cette façade, pour l'accroche
     * « la bonne période, c'est … ». Calculé depuis le profil, jamais saisi.
     *
     * @return array<int, string>
     */
    public function bestMonths(Destination $destination): array
    {
        $zone = $destination->climate_zone ?? ClimateZone::HautesTerres;

        return collect($zone->months())
            ->filter(fn (array $m) => SeasonKind::from($m[0])->isBest())
            ->keys()
            ->map(fn (int $i) => self::MOIS[$i])
            ->values()
            ->all();
    }

    /**
     * L'année entière, de janvier à décembre, pour le ruban.
     *
     * Douze segments lus d'un coup d'œil valent mieux qu'un paragraphe :
     * on voit *où est* la bonne saison avant de lire pourquoi. C'est la
     * seule vue de la page qui répond à « quand venir ? » sans faire défiler.
     *
     * @return array<int, array<string, mixed>>
     */
    public function year(Destination $destination): array
    {
        $zone = $destination->climate_zone ?? ClimateZone::HautesTerres;

        return collect($zone->months())
            ->map(function (array $m, int $i) {
                $kind = SeasonKind::from($m[0]);

                return new SeasonYearMonthData(
                    n: $i + 1,
                    month: self::MOIS[$i],
                    initial: mb_strtoupper(mb_substr(self::MOIS[$i], 0, 1)),
                    kind: $kind->value,
                    label: $kind->label(),
                    note: $m[1],
                    warning: $kind->isWarning(),
                    best: $kind->isBest(),
                );
            })
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public function saison(Destination $destination): SeasonData
    {
        return new SeasonData(
            zone: ($destination->climate_zone ?? ClimateZone::HautesTerres)->label(),
            months: $this->forDestination($destination),
            year: $this->year($destination),
            best: $this->bestMonths($destination),
            // Écrit à l'écran, pas seulement ici : le lecteur doit savoir ce
            // qu'il lit.
            caveat: 'Tendances de saison observées sur cette façade — pas une prévision météo.',
        );
    }
}
