<?php

namespace App\Data;

use App\Enums\AmenityGroup;
use App\Models\Listing;
use Spatie\LaravelData\Data;

/**
 * La fiche complète d'une annonce.
 *
 * Elle existe séparément de ListingData pour une raison de poids : la grille
 * d'accueil sert huit annonces, et chacune peut porter quarante équipements.
 * Les embarquer dans la liste multiplierait la charge utile par dix pour des
 * données que la carte n'affiche pas. La liste porte les trois équipements
 * mis en avant ; la fiche porte tout, groupé.
 */
class ListingDetailData extends Data
{
    /**
     * @param  array<int, AmenityGroupData>  $amenities
     * @param  array<int, PhotoData>  $gallery
     * @param  array<int, ConfirmationData>  $confirmations
     */
    public function __construct(
        public readonly ListingData $listing,
        public readonly ?string $description,
        public readonly array $amenities,
        public readonly array $gallery,
        public readonly StayRulesData $rules,
        public readonly ListingCalendarData $calendar,
        public readonly array $confirmations,
        public readonly ?ConfirmationSummaryData $confirmed,
    ) {}

    /**
     * @param  array<int, ConfirmationData>  $confirmations
     */
    public static function fromModel(
        Listing $listing,
        ListingCalendarData $calendar,
        array $confirmations = [],
        ?ConfirmationSummaryData $confirmed = null,
    ): self {
        return new self(
            listing: ListingData::fromModel($listing),
            description: $listing->description,
            amenities: self::grouped($listing),
            rules: StayRulesData::fromModel($listing),
            calendar: $calendar,
            confirmations: $confirmations,
            confirmed: $confirmed,
            // Chaque photo part avec son crédit : CC BY et CC BY-SA exigent
            // l'attribution partout où l'image est affichée, y compris dans
            // une visionneuse plein écran.
            gallery: $listing->photos->map(fn ($p) => PhotoData::fromModel($p))->values()->all(),
        );
    }

    /**
     * Les équipements rangés par rubrique, dans l'ordre de l'enum. Les
     * rubriques vides ne sortent pas : une fiche sans piscine ne doit pas
     * afficher un titre « Extérieur et piscine » suivi de rien.
     *
     * @return array<int, AmenityGroupData>
     */
    private static function grouped(Listing $listing): array
    {
        $byGroup = $listing->amenities
            ->sortBy('position')
            ->groupBy(fn ($a) => $a->group->value);

        $groups = [];

        foreach (AmenityGroup::ordered() as $group) {
            $rows = $byGroup->get($group->value);

            if (! $rows || $rows->isEmpty()) {
                continue;
            }

            $groups[] = AmenityGroupData::fromEnum(
                $group,
                $rows->map(fn ($a) => AmenityData::fromPivot($a))->values()->all()
            );
        }

        return $groups;
    }
}
