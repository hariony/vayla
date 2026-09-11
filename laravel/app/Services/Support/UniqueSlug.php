<?php

namespace App\Services\Support;

use Illuminate\Support\Str;

/**
 * Une clé publique libre, née d'un libellé : `nosy-be`, puis `nosy-be-2`.
 * **Elle ne bouge plus ensuite** — c'est une adresse, un filtre d'URL, un mot
 * de l'API mobile. Qui décide si une clé est prise, c'est l'appelant : ce
 * service ne connaît aucune table.
 */
final class UniqueSlug
{
    /** @param  callable(string): bool  $prise */
    public function pour(string $libelle, callable $prise): string
    {
        $base = Str::slug($libelle) ?: 'element';
        $slug = $base;

        for ($n = 2; $prise($slug); $n++) {
            $slug = "{$base}-{$n}";
        }

        return $slug;
    }
}
