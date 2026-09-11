<?php

namespace App\Repositories;

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Models\Category;
use Illuminate\Support\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function all(): Collection
    {
        return Category::query()->orderBy('position')->get();
    }

    public function findByKey(string $key): ?Category
    {
        return Category::query()->where('key', $key)->first();
    }
}
