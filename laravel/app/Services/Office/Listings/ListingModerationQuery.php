<?php

namespace App\Services\Office\Listings;

use App\Contracts\Office\JournalReader;
use App\Contracts\Repositories\OfficeListingRepositoryInterface;
use App\Data\Office\Listings\ListingDetailData;
use App\Data\Office\Listings\ListingModerationPageData;
use App\Data\Office\Listings\PublicationData;
use App\Data\Office\Listings\TrustRungChoiceData;
use App\Data\Office\OwnerCardData;
use App\Enums\ListingStatus;
use App\Enums\TrustLevel;
use App\Models\Listing;
use App\Services\Office\ModerationService;

/**
 * La fiche de modération. **Chaque barreau fermé écrit sa raison dessous**
 * avant qu'on clique — les mêmes phrases que le refus (`ModerationService`).
 */
final class ListingModerationQuery
{
    public function __construct(
        private OfficeListingRepositoryInterface $annonces,
        private ModerationService $moderation,
        private JournalReader $journal,
    ) {}

    public function page(Listing $listing): ListingModerationPageData
    {
        $listing = $this->annonces->pourModeration($listing);
        $raisonPublication = $this->moderation->pourquoiPasPublier($listing);

        return new ListingModerationPageData(
            annonce: ListingDetailData::fromModel($listing, $this->annonces->nombreConfirmations($listing), $this->annonces->nombreAVenir($listing)),
            proprietaire: $listing->owner ? OwnerCardData::fromModel($listing->owner) : null,
            niveaux: array_map(fn (TrustLevel $n) => new TrustRungChoiceData(
                niveau: $n->value,
                label: $n->label(),
                summary: $n->summary(),
                actuel: $n === $listing->trust_level,
                raison: $n === $listing->trust_level ? null : $this->moderation->pourquoiPasNiveau($listing, $n),
            ), TrustLevel::cases()),
            publication: new PublicationData(
                possible: $raisonPublication === null,
                raison: $raisonPublication,
                renvoyable: $listing->status === ListingStatus::Submitted,
                archivable: $listing->status !== ListingStatus::Archived,
            ),
            journal: $this->journal->pour($listing),
        );
    }
}
