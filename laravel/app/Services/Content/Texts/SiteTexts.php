<?php

namespace App\Services\Content\Texts;

use App\Contracts\Repositories\SiteTextRepositoryInterface;
use App\Support\SiteTextCatalog;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Les textes du site : l'original du catalogue, recouvert de ce que l'équipe
 * a réécrit.
 *
 * **Lu sur chaque page publique, écrit rarement** : le résultat est gardé en
 * cache jusqu'à la prochaine modification. Une requête par page pour relire
 * trente lignes qui changent une fois par mois serait un gaspillage.
 *
 * **Jamais une panne pour un texte** : une table absente ou une base qui ne
 * répond pas retombent sur le catalogue. L'accueil s'affiche avec son texte
 * d'origine plutôt que pas du tout.
 */
final class SiteTexts
{
    private const CACHE = 'site_texts:v1';

    public function __construct(
        private SiteTextRepositoryInterface $textes,
    ) {}

    /** @return array<string, string> tous les textes, clé → valeur affichée */
    public function tous(): array
    {
        try {
            $modifies = Cache::rememberForever(self::CACHE, fn () => $this->textes->valeurs());
        } catch (Throwable) {
            $modifies = [];
        }

        return array_merge(SiteTextCatalog::defauts(), array_intersect_key($modifies, SiteTextCatalog::defauts()));
    }

    public function oublier(): void
    {
        Cache::forget(self::CACHE);
    }
}
