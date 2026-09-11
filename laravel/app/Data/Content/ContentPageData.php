<?php

namespace App\Data\Content;

use Spatie\LaravelData\Data;

/** Les props de `Content/Show`. */
final class ContentPageData extends Data
{
    public function __construct(
        public readonly PublicPageData $page,
    ) {}
}
