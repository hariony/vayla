<?php

namespace App\Services\Content;

use App\Enums\AdminActionKind;
use App\Models\Admin;
use App\Models\Page;
use App\Services\Office\AdminJournal;
use App\Services\Office\OfficeRefusal;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

/**
 * Les pages éditoriales : « Comment ça marche », les tarifs, les pages légales…
 *
 * **Écrites en Markdown, affichées en HTML sûr.** Le Markdown est ce qu'on
 * tape sans apprendre un éditeur ; et le HTML brut qu'on y glisserait est
 * **retiré**, pas échappé ni exécuté (`html_input: strip`), comme les liens
 * `javascript:`. Un compte d'équipe compromis ne doit pas pouvoir poser un
 * script sur le site public.
 *
 * **Un chiffre qui existe ailleurs ne se recopie pas dans une page** :
 * `{commission}` s'écrit « 5 % » au moment de l'affichage, depuis le réglage.
 * Recopié à la main, le taux de la page « Tarifs » aurait menti le jour où il
 * aurait changé.
 *
 * **L'adresse d'une page ne bouge plus une fois publiée** — elle a pu être
 * partagée, imprimée, indexée. Et elle ne peut pas prendre celle d'un écran du
 * site : `/logements` ou `/connexion` resteraient ce qu'ils sont, et la page
 * serait introuvable.
 *
 * Le pied de page ne liste que les pages publiées : un lien vers une page en
 * brouillon serait un lien mort, et le pied de page en portait cinq.
 */
class PageService
{
    public const GROUPES = [
        'voyageurs' => 'Voyageurs',
        'proprietaires' => 'Propriétaires',
        'vayla' => 'Vayla',
        'legal' => 'Informations légales',
    ];

    /** Des adresses qui ne sont pas des écrans mais que le serveur réserve. */
    private const RESERVEES = ['api', 'up', 'images', 'build', 'storage', 'pages', 'favicon.ico', 'robots.txt', 'sitemap.xml', 'admin'];

    private const CACHE = 'pages:pied:v1';

    public function __construct(
        private AdminJournal $journal,
        private SettingsService $reglages,
    ) {}

    // ── Le site ─────────────────────────────────────────────────────────

    /** @return array<string, array<int, array{titre: string, href: string}>> */
    public function pied(): array
    {
        try {
            return Cache::rememberForever(self::CACHE, fn () => Page::query()
                ->where('is_published', true)
                ->whereNotNull('footer_group')
                ->orderBy('footer_position')
                ->orderBy('title')
                ->get(['slug', 'title', 'footer_group'])
                ->groupBy('footer_group')
                ->map(fn ($pages) => $pages->map(fn (Page $p) => ['titre' => $p->title, 'href' => '/'.$p->slug])->values()->all())
                ->all());
        } catch (Throwable) {
            return [];
        }
    }

    public function publiee(string $slug): ?Page
    {
        return Page::query()->where('slug', $slug)->where('is_published', true)->first();
    }

    /** @return array<string, mixed> */
    public function pourLeSite(Page $page): array
    {
        [$html, $sommaire] = $this->rendre((string) $page->body);

        return [
            'slug' => $page->slug,
            'titre' => $page->title,
            'accroche' => $page->lede,
            'groupe' => self::GROUPES[$page->footer_group] ?? null,
            'html' => $html,
            'sommaire' => $sommaire,
            'misAJour' => ($page->updated_at ?? $page->published_at)?->toDateString(),
            'description' => $page->seo_description ?: $page->lede,
        ];
    }

    /**
     * Le Markdown en HTML sûr, avec les ancres des intertitres et le sommaire.
     *
     * @return array{0: string, 1: array<int, array{id: string, label: string}>}
     */
    public function rendre(string $markdown): array
    {
        $markdown = str_replace('{commission}', $this->pourcent($this->reglages->commission()), $markdown);

        $html = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);

        $sommaire = [];
        $vus = [];

        $html = preg_replace_callback('/<h2>(.*?)<\/h2>/s', function (array $m) use (&$sommaire, &$vus) {
            $label = trim(strip_tags($m[1]));
            $id = Str::slug($label) ?: 'section';
            $id = isset($vus[$id]) ? $id.'-'.(++$vus[$id]) : $id;
            $vus[$id] ??= 1;
            $sommaire[] = ['id' => $id, 'label' => html_entity_decode($label, ENT_QUOTES | ENT_HTML5)];

            return "<h2 id=\"{$id}\">{$m[1]}</h2>";
        }, $html);

        // Un lien vers l'extérieur s'ouvre ailleurs, sans donner la main à la
        // page ouverte sur la nôtre.
        $html = preg_replace('/<a href="(https?:\/\/[^"]+)"/', '<a href="$1" target="_blank" rel="noopener noreferrer"', $html);

        return [$html, $sommaire];
    }

    // ── Le back-office ──────────────────────────────────────────────────

    /** @return array<int, array<string, mixed>> */
    public function liste(): array
    {
        return Page::query()
            ->orderByRaw('case when footer_group is null then 1 else 0 end')
            ->orderBy('footer_group')
            ->orderBy('footer_position')
            ->get()
            ->map(fn (Page $p) => $this->ligne($p))
            ->all();
    }

    /** @return array<string, mixed> */
    public function pourEdition(?Page $p): array
    {
        return [
            'page' => $p ? [
                ...$this->ligne($p),
                'lede' => $p->lede,
                'body' => $p->body,
                'seo_description' => $p->seo_description,
                'internal_note' => $p->internal_note,
                'adresseFigee' => $p->published_at !== null || $p->is_system,
            ] : null,
            'groupes' => collect(self::GROUPES)->map(fn ($label, $cle) => ['value' => $cle, 'label' => $label])->values()->all(),
            'commission' => $this->pourcent($this->reglages->commission()),
            'site' => rtrim((string) config('app.url'), '/'),
        ];
    }

    public function creer(Admin $admin, array $donnees): Page
    {
        $slug = Str::slug($donnees['slug'] ?? '') ?: Str::slug($donnees['title']);
        $this->verifierAdresse($slug);

        $page = Page::create([
            ...$this->champs($donnees),
            'slug' => $slug,
            'footer_position' => (int) Page::query()->where('footer_group', $donnees['footer_group'] ?? null)->max('footer_position') + 1,
            'updated_by' => $admin->id,
        ]);

        $this->oublier();
        $this->journal->consigner($admin, AdminActionKind::PageSaved, $page, "Page « {$page->title} » créée, en brouillon (/{$page->slug}).");

        return $page;
    }

    public function modifier(Admin $admin, Page $page, array $donnees): void
    {
        $champs = $this->champs($donnees);

        // L'adresse se corrige tant que la page n'a jamais été publiée ; après,
        // elle a pu être partagée — elle ne bouge plus.
        if (isset($donnees['slug']) && $page->published_at === null && ! $page->is_system) {
            $slug = Str::slug($donnees['slug']);
            if ($slug !== $page->slug) {
                $this->verifierAdresse($slug, $page->id);
                $champs['slug'] = $slug;
            }
        }

        $page->fill([...$champs, 'updated_by' => $admin->id])->save();

        $this->oublier();
        $this->journal->consigner($admin, AdminActionKind::PageSaved, $page, "Page « {$page->title} » modifiée.");
    }

    public function publier(Admin $admin, Page $page): void
    {
        if (trim((string) $page->body) === '') {
            throw new OfficeRefusal('La page est vide : écrivez son contenu avant de la publier.');
        }

        if (preg_match('/\[à compléter[^\]]*\]/iu', (string) $page->body.' '.$page->lede)) {
            throw new OfficeRefusal('La page contient encore des « [à compléter] » : remplissez-les avant de la publier.');
        }

        $page->forceFill(['is_published' => true, 'published_at' => $page->published_at ?? Carbon::now(), 'updated_by' => $admin->id])->save();

        $this->oublier();
        $this->journal->consigner($admin, AdminActionKind::PagePublished, $page, "Page « {$page->title} » publiée sur /{$page->slug}.");
    }

    public function depublier(Admin $admin, Page $page): void
    {
        $page->forceFill(['is_published' => false, 'updated_by' => $admin->id])->save();

        $this->oublier();
        $this->journal->consigner($admin, AdminActionKind::PageUnpublished, $page, "Page « {$page->title} » retirée du site.");
    }

    public function supprimer(Admin $admin, Page $page): void
    {
        if ($page->is_system) {
            throw new OfficeRefusal('Cette page est attendue par le site (pied de page, obligations légales) : elle se dépublie, elle ne se supprime pas.');
        }

        $this->journal->consigner($admin, AdminActionKind::PageDeleted, $page, "Page « {$page->title} » supprimée (/{$page->slug}).");
        $page->delete();
        $this->oublier();
    }

    /**
     * Une adresse libre : ni celle d'une autre page, ni celle d'un écran du
     * site. Les écrans sont lus sur le routeur lui-même — une liste recopiée
     * oublierait le prochain écran ajouté.
     */
    private function verifierAdresse(string $slug, ?int $sauf = null): void
    {
        if ($slug === '' || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new OfficeRefusal('L’adresse ne peut contenir que des lettres minuscules, des chiffres et des tirets.');
        }

        $ecrans = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => $r->getDomain() === null)
            ->map(fn ($r) => explode('/', trim($r->uri(), '/'))[0])
            ->filter(fn (string $s) => $s !== '' && ! str_starts_with($s, '{'))
            ->unique();

        if (in_array($slug, self::RESERVEES, true) || $ecrans->contains($slug)) {
            throw new OfficeRefusal("« /{$slug} » est déjà l’adresse d’un écran du site : choisissez-en une autre.");
        }

        if (Page::query()->where('slug', $slug)->when($sauf, fn ($q) => $q->where('id', '!=', $sauf))->exists()) {
            throw new OfficeRefusal("Une autre page occupe déjà « /{$slug} ».");
        }
    }

    /** @return array<string, mixed> */
    private function champs(array $d): array
    {
        return [
            'title' => trim($d['title']),
            'lede' => isset($d['lede']) ? (trim($d['lede']) ?: null) : null,
            'body' => $d['body'] ?? null,
            'seo_description' => isset($d['seo_description']) ? (trim($d['seo_description']) ?: null) : null,
            'footer_group' => $d['footer_group'] ?? null,
            'internal_note' => isset($d['internal_note']) ? (trim($d['internal_note']) ?: null) : null,
        ];
    }

    /** @return array<string, mixed> */
    private function ligne(Page $p): array
    {
        return [
            'id' => $p->id,
            'slug' => $p->slug,
            'title' => $p->title,
            'groupe' => self::GROUPES[$p->footer_group] ?? null,
            'footer_group' => $p->footer_group,
            'publiee' => $p->is_published,
            'systeme' => $p->is_system,
            'note' => (bool) $p->internal_note,
            'aCompleter' => (bool) preg_match('/\[à compléter[^\]]*\]/iu', (string) $p->body.' '.$p->lede),
            'misAJour' => $p->updated_at?->toIso8601String(),
            'url' => rtrim((string) config('app.url'), '/').'/'.$p->slug,
        ];
    }

    private function pourcent(float $taux): string
    {
        return rtrim(rtrim(number_format($taux * 100, 1, ',', ''), '0'), ',')."\u{00A0}%";
    }

    private function oublier(): void
    {
        Cache::forget(self::CACHE);
    }
}
