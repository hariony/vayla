<?php

namespace App\Repositories;

use App\Contracts\Repositories\OfficeCategoryRepositoryInterface;
use App\DTOs\Content\CategoryDto;
use App\Enums\StructuralCategory;
use App\Models\Category;
use App\Models\Listing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OfficeCategoryRepository implements OfficeCategoryRepositoryInterface
{
    public function toutes(): Collection
    {
        return Category::query()->withCount('listings')->orderBy('position')->orderBy('id')->get();
    }

    public function editoriales(): Collection
    {
        return Category::query()->whereNotIn('key', StructuralCategory::cles())->orderBy('position')->get();
    }

    public function idsEditoriaux(array $ids): array
    {
        return Category::query()
            ->whereIn('id', array_map('intval', $ids))
            ->whereNotIn('key', StructuralCategory::cles())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function ordre(): array
    {
        return Category::query()->orderBy('position')->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function cleExiste(string $cle): bool
    {
        return Category::query()->where('key', $cle)->exists();
    }

    public function positionSuivante(): int
    {
        return (int) Category::query()->max('position') + 1;
    }

    public function creer(string $cle, int $position, CategoryDto $categorie): Category
    {
        return Category::create([
            'key' => $cle,
            'position' => $position,
            'label' => $categorie->label,
            'icon' => $categorie->icon->value,
            'sponsored' => $categorie->sponsored,
        ]);
    }

    public function modifier(Category $categorie, CategoryDto $donnees): void
    {
        $categorie->fill(['label' => $donnees->label, 'icon' => $donnees->icon->value, 'sponsored' => $donnees->sponsored])->save();
    }

    public function ordonner(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $position => $id) {
                Category::query()->whereKey($id)->update(['position' => $position]);
            }
        });
    }

    public function supprimer(Category $categorie): void
    {
        $categorie->delete();
    }

    public function nombreAnnonces(Category $categorie): int
    {
        return $categorie->listings()->count();
    }

    public function comptesStructurels(): array
    {
        return [
            StructuralCategory::Tout->value => Listing::query()->count(),
            StructuralCategory::Verifie->value => Listing::query()->where('trust_level', 4)->count(),
        ];
    }
}
