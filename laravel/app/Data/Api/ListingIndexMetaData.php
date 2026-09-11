<?php

namespace App\Data\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

/**
 * La pagination de `/api/v1/listings`. Les noms sont ceux de l'API publiée
 * (`per_page`) : l'application mobile les lit, ils ne bougent pas.
 */
final class ListingIndexMetaData extends Data
{
    public function __construct(
        public readonly int $page,
        #[MapOutputName('per_page')]
        public readonly int $perPage,
        public readonly int $total,
        public readonly int $pages,
        public readonly bool $demo,
    ) {}

    public static function fromPaginator(LengthAwarePaginator $page, bool $demo): self
    {
        return new self($page->currentPage(), $page->perPage(), $page->total(), $page->lastPage(), $demo);
    }
}
