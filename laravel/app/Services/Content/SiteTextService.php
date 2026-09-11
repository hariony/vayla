<?php

namespace App\Services\Content;

use App\Enums\AdminActionKind;
use App\Models\Admin;
use App\Models\SiteText;
use App\Services\Office\AdminJournal;
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
class SiteTextService
{
    private const CACHE = 'site_texts:v1';

    public function __construct(
        private AdminJournal $journal,
    ) {}

    /** @return array<string, string> tous les textes, clé → valeur affichée */
    public function tous(): array
    {
        try {
            $modifies = Cache::rememberForever(self::CACHE, fn () => SiteText::query()->pluck('value', 'key')->all());
        } catch (Throwable) {
            $modifies = [];
        }

        return array_merge(SiteTextCatalog::defauts(), array_intersect_key($modifies, SiteTextCatalog::defauts()));
    }

    /**
     * Enregistre les textes d'un groupe. **Une valeur égale à l'original
     * supprime la ligne** : c'est ce que fait « Rétablir », et c'est ce qui
     * garde la base réduite à ce qui a vraiment été réécrit.
     *
     * @param  array<string, string>  $valeurs
     * @return array<int, string> les libellés des textes changés
     */
    public function enregistrer(Admin $admin, string $groupe, array $valeurs): array
    {
        $definitions = SiteTextCatalog::GROUPES[$groupe]['textes'] ?? [];
        $actuels = $this->tous();
        $changes = [];

        foreach ($definitions as $cle => $def) {
            if (! array_key_exists($cle, $valeurs)) {
                continue;
            }

            $valeur = $this->normaliser((string) $valeurs[$cle]);

            if ($valeur === ($actuels[$cle] ?? null)) {
                continue;
            }

            $valeur === $def['defaut'] || $valeur === ''
                ? SiteText::query()->where('key', $cle)->delete()
                : SiteText::query()->updateOrCreate(['key' => $cle], ['value' => $valeur, 'admin_id' => $admin->id]);

            $changes[] = $def['label'];
        }

        if ($changes !== []) {
            Cache::forget(self::CACHE);
            $this->journal->consigner($admin, AdminActionKind::SiteTextsChanged, null,
                SiteTextCatalog::GROUPES[$groupe]['titre'].' : '.implode(', ', $changes).'.');
        }

        return $changes;
    }

    /** @return array<int, array<string, mixed>> les groupes, pour l'écran du back-office */
    public function groupes(): array
    {
        $lignes = SiteText::query()->with('admin')->get()->keyBy('key');
        $site = rtrim((string) config('app.url'), '/');

        return collect(SiteTextCatalog::GROUPES)->map(fn (array $g, string $cle) => [
            'cle' => $cle,
            'titre' => $g['titre'],
            'note' => $g['note'] ?? null,
            'lien' => $site.$g['ancre'],
            'textes' => collect($g['textes'])->map(fn (array $d, string $k) => [
                'cle' => $k,
                'label' => $d['label'],
                'type' => $d['type'],
                'max' => $d['max'],
                'aide' => $d['aide'] ?? null,
                'defaut' => $d['defaut'],
                'valeur' => $lignes[$k]->value ?? $d['defaut'],
                'modifie' => isset($lignes[$k]),
                'le' => isset($lignes[$k]) ? $lignes[$k]->updated_at?->toIso8601String() : null,
            ])->values()->all(),
        ])->values()->all();
    }

    /** Les espaces en trop et les fins de ligne Windows ne sont pas du texte. */
    private function normaliser(string $v): string
    {
        return trim(preg_replace("/[ \t]+\n/", "\n", str_replace("\r\n", "\n", $v)));
    }
}
