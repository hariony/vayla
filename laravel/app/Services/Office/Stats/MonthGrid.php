<?php

namespace App\Services\Office\Stats;

use App\Enums\StatsPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Les mois d'une période, du plus ancien au courant, et le rangement d'une
 * série dedans. **Un mois sans ligne reçoit ce que le calcul rend sur une
 * liste vide** : zéro pour un compte, `null` pour un taux — la courbe
 * s'interrompt au lieu de plonger.
 *
 * Le regroupement se fait en PHP, pas en SQL : les fonctions de date diffèrent
 * entre PostgreSQL et SQLite, et les volumes tiennent en mémoire.
 */
final readonly class MonthGrid
{
    /** @param  list<string>  $cles  « AAAA-MM » */
    private function __construct(
        public Carbon $debut,
        private array $cles,
    ) {}

    public static function sur(StatsPeriod $periode): self
    {
        $debut = Carbon::today()->startOfMonth()->subMonthsNoOverflow($periode->value - 1);

        return new self($debut, array_map(
            fn (int $i) => $debut->copy()->addMonthsNoOverflow($i)->format('Y-m'),
            range(0, $periode->value - 1),
        ));
    }

    /** @return list<string> « sept. 26 » */
    public function courts(): array
    {
        return $this->libelles('M y');
    }

    /** @return list<string> « septembre 2026 » */
    public function longs(): array
    {
        return $this->libelles('F Y');
    }

    /**
     * Une valeur par mois : `$calcul` reçoit les lignes rangées dans ce mois.
     *
     * @param  callable(mixed): string  $mois  « AAAA-MM » d'une ligne
     * @param  callable(Collection): mixed  $calcul
     * @return list<mixed>
     */
    public function parMois(Collection $lignes, callable $mois, callable $calcul): array
    {
        $groupes = $lignes->groupBy(fn ($ligne) => $mois($ligne));

        return array_map(fn (string $cle) => $calcul($groupes->get($cle, new Collection)), $this->cles);
    }

    /** @return list<int> */
    public function compter(Collection $lignes, callable $mois): array
    {
        return $this->parMois($lignes, $mois, fn (Collection $l) => $l->count());
    }

    /** @return list<int|float> */
    public function additionner(Collection $lignes, callable $mois, callable $valeur): array
    {
        return $this->parMois($lignes, $mois, fn (Collection $l) => $l->sum($valeur));
    }

    /** @return list<string> « AAAA-MM », du plus ancien au mois en cours */
    public function cles(): array
    {
        return $this->cles;
    }

    /**
     * Un mois clos est un mois dont la facture existe : **le mois en cours
     * n'en est pas encore une**, rien n'y est dû.
     */
    public function estClos(string $cle): bool
    {
        return $cle < $this->cles[array_key_last($this->cles)];
    }

    /** « sept. 26 » */
    public function libelleCourt(string $cle): string
    {
        return self::formater($cle, 'M y');
    }

    /** « septembre 2026 » */
    public function libelleLong(string $cle): string
    {
        return self::formater($cle, 'F Y');
    }

    /** @return list<string> */
    private function libelles(string $format): array
    {
        return array_map(fn (string $cle) => self::formater($cle, $format), $this->cles);
    }

    private static function formater(string $cle, string $format): string
    {
        return Carbon::createFromFormat('Y-m-d', "{$cle}-01")->translatedFormat($format);
    }
}
