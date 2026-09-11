<?php

namespace App\Services\Office\Content;

use App\Contracts\Office\ActionJournal;
use App\Contracts\Repositories\OfficeCategoryRepositoryInterface;
use App\DTOs\Content\CategoryDto;
use App\Enums\AdminActionKind;
use App\Enums\PositionShift;
use App\Enums\StructuralCategory;
use App\Exceptions\OfficeRefusal;
use App\Models\Admin;
use App\Models\Category;
use App\Services\Support\PositionSwapper;
use App\Services\Support\UniqueSlug;

/**
 * Le rail de catégories. **Une clé ne bouge jamais** — c'est un filtre d'URL
 * et un mot de l'API mobile ; on renomme le libellé. **Une place achetée se
 * dit** : `sponsored` affiche « Sponsorisé » sur le site. « Séjour confirmé »
 * se déduit du niveau 4 et **ne se vend pas**.
 */
final class CategoryEditor
{
    public function __construct(
        private OfficeCategoryRepositoryInterface $categories,
        private UniqueSlug $slugs,
        private PositionSwapper $positions,
        private ActionJournal $journal,
    ) {}

    public function creer(Admin $admin, CategoryDto $saisie): Category
    {
        $cle = $this->slugs->pour($saisie->label, fn (string $s) => $this->categories->cleExiste($s));
        $categorie = $this->categories->creer($cle, $this->categories->positionSuivante(), $saisie);

        $this->consigner($admin, $categorie, "Catégorie « {$categorie->label} » créée");

        return $categorie;
    }

    public function modifier(Admin $admin, Category $categorie, CategoryDto $saisie): void
    {
        if ($categorie->key === StructuralCategory::Verifie->value && $saisie->sponsored) {
            throw new OfficeRefusal('« Séjour confirmé » se déduit du niveau 4 : cette place ne se vend pas.');
        }

        $this->categories->modifier($categorie, $saisie);
        $this->consigner($admin, $categorie, "Catégorie « {$categorie->label} » modifiée");
    }

    public function deplacer(Admin $admin, Category $categorie, PositionShift $sens): void
    {
        $ordre = $this->positions->deplacer($this->categories->ordre(), $categorie->id, $sens);

        if ($ordre !== null) {
            $this->categories->ordonner($ordre);
        }

        $this->journal->consigner($admin, AdminActionKind::CategorySaved, $categorie, "Catégorie « {$categorie->label} » déplacée dans le rail.");
    }

    /** « Tout » et « Séjour confirmé » sont des filtres : ils se renomment, ils ne se suppriment pas. */
    public function supprimer(Admin $admin, Category $categorie): void
    {
        if (StructuralCategory::est($categorie->key)) {
            throw new OfficeRefusal("« {$categorie->label} » n’est pas une étiquette mais un filtre du rail : elle se renomme, elle ne se supprime pas.");
        }

        $n = $this->categories->nombreAnnonces($categorie);
        $this->journal->consigner($admin, AdminActionKind::CategoryDeleted, $categorie,
            "Catégorie « {$categorie->label} » supprimée".($n ? " — {$n} logement".($n > 1 ? 's' : '').' ne la portent plus.' : '.'));
        $this->categories->supprimer($categorie);
    }

    private function consigner(Admin $admin, Category $categorie, string $resume): void
    {
        $this->journal->consigner($admin, AdminActionKind::CategorySaved, $categorie,
            $resume.($categorie->sponsored ? ' — mise en avant payée, signalée sur le site.' : '.'));
    }
}
