<?php

namespace App\Services\Content\Pages;

use App\Contracts\Repositories\PageRepositoryInterface;
use App\Data\Content\ContentPageData;
use App\Data\Content\FooterLinkData;
use App\Data\Content\PublicPageData;
use App\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Les pages éditoriales, côté site : la page publiée, et les liens du pied de
 * page.
 *
 * **Le pied de page ne liste que les pages publiées** : un lien vers une page
 * en brouillon serait un lien mort, et le pied de page en portait cinq. Il est
 * lu sur chaque page et change une fois par mois : **en cache jusqu'à la
 * prochaine modification** — et jamais une panne pour lui, une base qui ne
 * répond pas donne un pied de page sans ces liens.
 *
 * Le cache ne garde que des valeurs simples : `cache.serializable_classes` est
 * à `false`, un objet y reviendrait inutilisable.
 */
final class SitePages
{
    private const CACHE = 'pages:pied:v2';

    public function __construct(
        private PageRepositoryInterface $pages,
        private PageRenderer $rendu,
    ) {}

    /** @return array<string, list<FooterLinkData>> colonne du pied de page → liens */
    public function pied(): array
    {
        try {
            $liens = Cache::rememberForever(self::CACHE, fn () => $this->pages->pourPied()
                ->map(fn (Page $p) => ['groupe' => $p->footer_group, 'titre' => $p->title, 'href' => '/'.$p->slug])
                ->all());
        } catch (Throwable) {
            return [];
        }

        return collect($liens)
            ->groupBy('groupe')
            ->map(fn (Collection $l) => $l->map(fn (array $lien) => new FooterLinkData($lien['titre'], $lien['href']))->values()->all())
            ->all();
    }

    /** `null` pour une page en brouillon comme pour une adresse inconnue. */
    public function publiee(string $slug): ?ContentPageData
    {
        $page = $this->pages->publiee($slug);

        return $page ? new ContentPageData(PublicPageData::fromModel($page, $this->rendu->rendre((string) $page->body))) : null;
    }

    /** Après tout geste sur une page : le pied de page se relira. */
    public function oublierPied(): void
    {
        Cache::forget(self::CACHE);
    }
}
