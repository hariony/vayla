<?php

namespace App\Data\Office;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Data;

/** De quoi paginer une liste — voir `OfficePager.vue`. */
final class PaginationData extends Data
{
    public function __construct(
        public readonly int $page,
        public readonly int $pages,
        public readonly int $total,
        public readonly ?string $precedente,
        public readonly ?string $suivante,
    ) {}

    public static function fromPaginator(LengthAwarePaginator $page): self
    {
        return new self(
            page: $page->currentPage(),
            pages: $page->lastPage(),
            total: $page->total(),
            precedente: $page->previousPageUrl(),
            suivante: $page->nextPageUrl(),
        );
    }
}
