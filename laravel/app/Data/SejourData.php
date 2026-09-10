<?php

namespace App\Data;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

/**
 * Un séjour demandé : deux dates, et la règle qui les relie.
 *
 * **Une nuit appartient à sa date d'arrivée.** Un séjour du 12 au 15 fait
 * trois nuits — 12, 13, 14 — et libère le 15 : la date de départ n'est jamais
 * occupée. Cette règle décide du prix, du calendrier, du blocage des dates et
 * maintenant du filtre de recherche. Elle était déjà écrite deux fois
 * (`AvailabilityService`, `BookingService`) ; l'écrire une troisième fois dans
 * le dépôt aurait garanti qu'une des trois finisse par mentir.
 *
 * D'où cet objet : la soustraction d'un jour se fait ici, et nulle part
 * ailleurs. Les appelants ne manipulent que `derniereNuit()` et `couvre()`.
 *
 * Les dates sont des chaînes `AAAA-MM-JJ`, jamais des `Date` : côté SQL comme
 * côté JavaScript, une date sans heure se compare correctement en texte, et
 * `new Date()` en heure locale décale d'un jour à Madagascar (UTC+3).
 */
class SejourData extends Data
{
    private function __construct(
        public readonly string $arrival,
        public readonly string $departure,
        public readonly int $nights,
    ) {}

    /**
     * Construit le séjour, ou `null` si les deux dates ne forment pas un
     * séjour valide. Le filtre est **tout ou rien** : une arrivée seule ne
     * filtre rien, et un critère qui ne filtre rien en silence est pire que
     * pas de critère du tout — c'était exactement le défaut du moteur de
     * recherche, qui collectait deux dates et les jetait.
     */
    public static function depuis(?string $arrival, ?string $departure): ?self
    {
        $debut = self::jour($arrival);
        $fin = self::jour($departure);

        if (! $debut || ! $fin || $fin->lessThanOrEqualTo($debut)) {
            return null;
        }

        return new self(
            arrival: $debut->toDateString(),
            departure: $fin->toDateString(),
            nights: (int) $debut->diffInDays($fin),
        );
    }

    /**
     * Une date `AAAA-MM-JJ`, ou `null`.
     *
     * Deux garde-fous, et les deux ont mordu : `createFromFormat` **lève** sur
     * une chaîne quelconque, et PHP accepte silencieusement `2026-13-45` en le
     * reportant sur février 2027 — un mois inexistant deviendrait une date
     * valide, et le séjour suggéré porterait sur des nuits que personne n'a
     * demandées. L'aller-retour par `format()` refuse ces reports.
     */
    private static function jour(?string $iso): ?Carbon
    {
        if (! $iso || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $iso)) {
            return null;
        }

        try {
            // `!` remet l'heure à minuit : sans lui, la date porte l'heure
            // courante et deux séjours identiques ne se comparent plus.
            $jour = Carbon::createFromFormat('!Y-m-d', $iso);
        } catch (\Throwable) {
            return null;
        }

        return $jour?->format('Y-m-d') === $iso ? $jour : null;
    }

    /** La dernière nuit occupée par ce séjour. Le jour du départ n'en fait pas partie. */
    public function derniereNuit(): string
    {
        return Carbon::createFromFormat('Y-m-d', $this->departure)->subDay()->toDateString();
    }

    /**
     * Ce séjour croise-t-il la période occupée [$debut, $fin] ?
     *
     * Les deux bornes reçues sont des **nuits occupées**, inclusives — c'est
     * la forme que prennent `unavailabilities` et les périodes rendues par
     * `AvailabilityService::blocked()`.
     */
    public function couvre(string $debut, string $fin): bool
    {
        return $debut <= $this->derniereNuit() && $fin >= $this->arrival;
    }
}
