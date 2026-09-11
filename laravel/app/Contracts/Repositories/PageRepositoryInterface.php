<?php

namespace App\Contracts\Repositories;

use App\DTOs\Content\PageDto;
use App\Models\Admin;
use App\Models\Page;
use Illuminate\Support\Collection;

/** Les pages éditoriales du site. */
interface PageRepositoryInterface
{
    /** @return Collection<int, Page> publiées et rangées dans le pied de page, dans l'ordre */
    public function pourPied(): Collection;

    public function publiee(string $slug): ?Page;

    /** @return Collection<int, Page> par colonne du pied de page, les pages hors pied en dernier */
    public function toutes(): Collection;

    public function adresseOccupee(string $slug, ?int $sauf = null): bool;

    /** En brouillon, au bout de sa colonne. */
    public function creer(Admin $par, PageDto $contenu, string $slug): Page;

    /** `$slug` : la nouvelle adresse, ou `null` pour garder l'actuelle. */
    public function modifier(Admin $par, Page $page, PageDto $contenu, ?string $slug): void;

    /** La date de première publication ne bouge plus : c'est elle qui fige l'adresse. */
    public function publier(Admin $par, Page $page): void;

    public function depublier(Admin $par, Page $page): void;

    public function supprimer(Page $page): void;
}
