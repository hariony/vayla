<?php

namespace App\Data\Office;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Data;

/** Une page de liste : les lignes, et de quoi paginer. */
final class PaginatedData extends Data
{
    /** @param  array<int, Data>  $data */
    public function __construct(
        public readonly array $data,
        public readonly PaginationData $meta,
    ) {}

    /** @param  callable(mixed): Data  $ligne */
    public static function fromPaginator(LengthAwarePaginator $page, callable $ligne): self
    {
        return new self(
            collect($page->items())->map($ligne)->values()->all(),
            PaginationData::fromPaginator($page),
        );
    }
}
