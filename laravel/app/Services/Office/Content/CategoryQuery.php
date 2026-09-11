<?php

namespace App\Services\Office\Content;

use App\Contracts\Repositories\OfficeCategoryRepositoryInterface;
use App\Data\Office\Content\CategoriesPageData;
use App\Data\Office\Content\CategoryRowData;
use App\Data\OptionData;
use App\Enums\CategoryIcon;
use App\Enums\StructuralCategory;
use App\Models\Category;

/** Le rail de catégories, vu par l'équipe. */
final class CategoryQuery
{
    public function __construct(private OfficeCategoryRepositoryInterface $categories) {}

    public function page(): CategoriesPageData
    {
        $structurels = $this->categories->comptesStructurels();

        return new CategoriesPageData(
            categories: $this->categories->toutes()->map(fn (Category $c) => new CategoryRowData(
                id: $c->id,
                key: $c->key,
                label: $c->label,
                icon: $c->icon,
                sponsored: (bool) $c->sponsored,
                structurelle: StructuralCategory::est($c->key),
                // Un filtre structurel ne se pose sur rien : il montre ce qu'il filtre.
                listings: $structurels[$c->key] ?? (int) $c->listings_count,
            ))->all(),
            icones: array_map(fn (CategoryIcon $i) => new OptionData($i->value, $i->label()), CategoryIcon::cases()),
        );
    }
}
