<?php

namespace App\Data\Pages;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Data;

/** Où l'on en est dans les pages du catalogue. */
final class CatalogueMetaData extends Data
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $total,
        public readonly int $pages,
    ) {}

    public static function fromPaginator(LengthAwarePaginator $page): self
    {
        return new self($page->currentPage(), $page->perPage(), $page->total(), $page->lastPage());
    }
}
