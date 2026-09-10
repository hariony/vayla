<?php

namespace App\Enums;

/**
 * Ce qu'un mois vaut, pour une façade donnée.
 *
 * Cinq états, et l'un d'eux est un avertissement. Un calendrier qui ne
 * saurait que vanter les bonnes périodes ne renseignerait rien : c'est
 * précisément « cyclones possibles » en février qui rend « meilleure
 * période » crédible en août.
 */
enum SeasonKind: string
{
    case Ideale = 'ideale';
    case Seche = 'seche';
    case Pluies = 'pluies';
    case Cyclones = 'cyclones';
    case Froid = 'froid';
    case Chaud = 'chaud';

    public function label(): string
    {
        return match ($this) {
            self::Ideale => 'Meilleure période',
            self::Seche => 'Saison sèche',
            self::Pluies => 'Saison des pluies',
            self::Cyclones => 'Risque cyclonique',
            self::Froid => 'Nuits froides',
            self::Chaud => 'Forte chaleur',
        };
    }

    /** Clé de pictogramme, résolue par le front. */
    public function icon(): string
    {
        return match ($this) {
            self::Ideale => 'sun',
            self::Seche => 'sun',
            self::Pluies => 'drop',
            self::Cyclones => 'wave',
            self::Froid => 'snow',
            self::Chaud => 'flame',
        };
    }

    /**
     * Seul le risque cyclonique est un avertissement. Le distinguer évite
     * que le front décide lui-même de ce qui est grave.
     */
    public function isWarning(): bool
    {
        return $this === self::Cyclones;
    }

    /** Une période qu'on recommande franchement. */
    public function isBest(): bool
    {
        return $this === self::Ideale;
    }
}
