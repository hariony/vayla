<?php

namespace App\Services\Content\Pages;

use App\Contracts\Office\ActionJournal;
use App\Contracts\Repositories\PageRepositoryInterface;
use App\DTOs\Content\PageDto;
use App\Enums\AdminActionKind;
use App\Exceptions\OfficeRefusal;
use App\Models\Admin;
use App\Models\Page;
use App\Support\ACompleter;
use Illuminate\Support\Str;

/**
 * Les gestes sur les pages éditoriales.
 *
 * **L'adresse d'une page ne bouge plus une fois publiée** — elle a pu être
 * partagée, imprimée, indexée. **Publier est un geste à part d'enregistrer**,
 * et une page qui porte encore « [à compléter] » refuse de se publier. Les
 * pages attendues par le site (`is_system`) se dépublient, elles ne se
 * suppriment pas.
 */
final class PageEditor
{
    public function __construct(
        private PageRepositoryInterface $pages,
        private PageAddresses $adresses,
        private SitePages $site,
        private ActionJournal $journal,
    ) {}

    public function creer(Admin $par, PageDto $contenu): Page
    {
        $slug = Str::slug((string) $contenu->slug) ?: Str::slug($contenu->title);
        $this->adresses->verifier($slug);

        $page = $this->pages->creer($par, $contenu, $slug);

        $this->site->oublierPied();
        $this->journal->consigner($par, AdminActionKind::PageSaved, $page, "Page « {$page->title} » créée, en brouillon (/{$page->slug}).");

        return $page;
    }

    public function modifier(Admin $par, Page $page, PageDto $contenu): void
    {
        $this->pages->modifier($par, $page, $contenu, $this->nouvelleAdresse($page, $contenu->slug));

        $this->site->oublierPied();
        $this->journal->consigner($par, AdminActionKind::PageSaved, $page, "Page « {$page->title} » modifiée.");
    }

    public function publier(Admin $par, Page $page): void
    {
        if (trim((string) $page->body) === '') {
            throw new OfficeRefusal('La page est vide : écrivez son contenu avant de la publier.');
        }

        if (ACompleter::present($page->body, $page->lede)) {
            throw new OfficeRefusal('La page contient encore des « [à compléter] » : remplissez-les avant de la publier.');
        }

        $this->pages->publier($par, $page);

        $this->site->oublierPied();
        $this->journal->consigner($par, AdminActionKind::PagePublished, $page, "Page « {$page->title} » publiée sur /{$page->slug}.");
    }

    public function depublier(Admin $par, Page $page): void
    {
        $this->pages->depublier($par, $page);

        $this->site->oublierPied();
        $this->journal->consigner($par, AdminActionKind::PageUnpublished, $page, "Page « {$page->title} » retirée du site.");
    }

    public function supprimer(Admin $par, Page $page): void
    {
        if ($page->is_system) {
            throw new OfficeRefusal('Cette page est attendue par le site (pied de page, obligations légales) : elle se dépublie, elle ne se supprime pas.');
        }

        $this->journal->consigner($par, AdminActionKind::PageDeleted, $page, "Page « {$page->title} » supprimée (/{$page->slug}).");
        $this->pages->supprimer($page);
        $this->site->oublierPied();
    }

    /**
     * La nouvelle adresse, ou `null` pour garder l'actuelle. Elle se corrige
     * tant que la page n'a jamais été publiée ; après, elle ne bouge plus.
     */
    private function nouvelleAdresse(Page $page, ?string $proposee): ?string
    {
        if ($proposee === null || $page->published_at !== null || $page->is_system) {
            return null;
        }

        $slug = Str::slug($proposee);

        if ($slug === $page->slug) {
            return null;
        }

        $this->adresses->verifier($slug, $page->id);

        return $slug;
    }
}
