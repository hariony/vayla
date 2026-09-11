<?php

namespace App\Contracts\Repositories;

use App\DTOs\Content\CategoryDto;
use App\Models\Category;
use Illuminate\Support\Collection;

/** Les catégories du rail, vues par l'équipe. */
interface OfficeCategoryRepositoryInterface
{
    /** @return Collection<int, Category> dans l'ordre du rail, avec `listings_count` */
    public function toutes(): Collection;

    /** @return Collection<int, Category> celles qui se posent sur une annonce, dans l'ordre du rail */
    public function editoriales(): Collection;

    /**
     * @param  array<int, int>  $ids
     * @return array<int, int> ceux qui désignent une catégorie éditoriale
     */
    public function idsEditoriaux(array $ids): array;

    /** @return array<int, int> les identifiants, dans l'ordre du rail */
    public function ordre(): array;

    public function cleExiste(string $cle): bool;

    public function positionSuivante(): int;

    public function creer(string $cle, int $position, CategoryDto $categorie): Category;

    public function modifier(Category $categorie, CategoryDto $donnees): void;

    /** @param  array<int, int>  $ids  la position vaut l'index */
    public function ordonner(array $ids): void;

    public function supprimer(Category $categorie): void;

    public function nombreAnnonces(Category $categorie): int;

    /**
     * Ce que montrent les deux filtres structurels : « Tout », toutes les
     * annonces ; « Séjour confirmé », celles du niveau 4.
     *
     * @return array<string, int> par clé de `StructuralCategory`
     */
    public function comptesStructurels(): array;
}
