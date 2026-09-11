<?php

namespace App\Support;

/**
 * La marque « [à compléter] » que portent les pages nées en brouillon — les
 * pages légales demandent des faits que le dépôt n'a pas. **Tant qu'elle est
 * là, la page ne se publie pas** : un texte juridique inventé serait pire que
 * pas de texte.
 */
final class ACompleter
{
    public static function present(?string ...$textes): bool
    {
        return (bool) preg_match('/\[à compléter[^\]]*\]/iu', implode(' ', array_map('strval', $textes)));
    }
}
