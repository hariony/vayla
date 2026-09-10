<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Support\Collection;

interface CategoryRepositoryInterface
{
    /** @return Collection<int, Category> */
    public function all(): Collection;

    public function findByKey(string $key): ?Category;
}
