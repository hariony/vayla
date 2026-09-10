<?php

namespace App\Services;

use App\Data\CategoryData;
use App\Repositories\Contracts\CategoryRepositoryInterface;

class CategoryService
{
    public function __construct(
        private CategoryRepositoryInterface $repository,
    ) {}

    /** @return array<int, CategoryData> */
    public function rail(): array
    {
        return $this->repository->all()
            ->map(fn ($c) => CategoryData::fromModel($c))
            ->values()
            ->all();
    }
}
