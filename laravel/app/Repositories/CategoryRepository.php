<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
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
