<?php

namespace App\Services\Office\Content;

use App\Contracts\Listings\ListingDrafting;
use App\Contracts\Repositories\OfficeCategoryRepositoryInterface;
use App\Contracts\Repositories\OfficeListingContentRepositoryInterface;
use App\Data\Office\Content\EditorialCategoryData;
use App\Data\Office\Content\ListingEditPageData;
use App\Data\Office\OwnerRefData;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Owner;

/** Le formulaire de contenu d'une annonce — le même que celui du propriétaire, plus la mise en avant et les catégories. */
final class ListingContentQuery
{
    public function __construct(
        private ListingDrafting $redaction,
        private OfficeListingContentRepositoryInterface $contenu,
        private OfficeCategoryRepositoryInterface $categories,
    ) {}

    public function page(?Listing $listing, ?Owner $owner = null): ListingEditPageData
    {
        $listing = $listing ? $this->contenu->charger($listing) : null;
        $owner ??= $listing?->owner;

        return new ListingEditPageData(
            annonce: $listing ? [
                ...$this->redaction->pourEdition($listing),
                'id' => $listing->id,
                'featured' => (bool) $listing->featured,
                'categories' => $this->contenu->categories($listing),
                'isDemo' => (bool) $listing->is_demo,
                'publicUrl' => rtrim((string) config('app.url'), '/').'/logements/'.$listing->slug,
            ] : null,
            proprietaire: $owner ? new OwnerRefData($owner->id, $owner->name) : null,
            vocabulaire: [
                ...$this->redaction->vocabulaire(),
                'categories' => $this->categories->editoriales()
                    ->map(fn (Category $c) => EditorialCategoryData::fromModel($c))->all(),
            ],
        );
    }
}
