<?php

namespace App\Data;

use App\Models\Category;
use Spatie\LaravelData\Data;

class CategoryData extends Data
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $icon,
        public readonly bool $sponsored = false,
    ) {}

    public static function fromModel(Category $category): self
    {
        return new self(
            key: $category->key,
            label: $category->label,
            icon: $category->icon,
            sponsored: $category->sponsored,
        );
    }
}
