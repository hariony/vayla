<?php

namespace App\Data\Content;

use App\Enums\PageGroup;
use App\Models\Page;
use Spatie\LaravelData\Data;

/** Une page éditoriale telle que le site la montre. `description` retombe sur l'accroche. */
final class PublicPageData extends Data
{
    /** @param  list<PageHeadingData>  $sommaire */
    public function __construct(
        public readonly string $slug,
        public readonly string $titre,
        public readonly ?string $accroche,
        public readonly ?string $groupe,
        public readonly string $html,
        public readonly array $sommaire,
        public readonly ?string $misAJour,
        public readonly ?string $description,
    ) {}

    public static function fromModel(Page $page, RenderedPageData $rendu): self
    {
        return new self(
            slug: $page->slug,
            titre: $page->title,
            accroche: $page->lede,
            groupe: PageGroup::tryFrom((string) $page->footer_group)?->label(),
            html: $rendu->html,
            sommaire: $rendu->sommaire,
            misAJour: ($page->updated_at ?? $page->published_at)?->toDateString(),
            description: $page->seo_description ?: $page->lede,
        );
    }
}
