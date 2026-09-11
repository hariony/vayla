<?php

namespace App\Http\Requests\Office\Concerns;

/**
 * La recherche d'une liste : sans espaces autour, bornée à 80 caractères,
 * `null` quand elle est vide. Écrite une fois — elle l'était dans quatre
 * contrôleurs.
 */
trait ReadsSearch
{
    public function recherche(): ?string
    {
        $q = trim((string) $this->query('q'));

        return $q === '' ? null : mb_substr($q, 0, 80);
    }
}
