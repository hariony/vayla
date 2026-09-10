<?php

namespace App\Data;

use App\Enums\TrustLevel;
use App\Models\Listing;
use Spatie\LaravelData\Data;

/**
 * Le contrat d'une annonce dans une liste, partagé par Inertia et par l'API
 * mobile. La fiche complète, elle, passe par ListingDetailData.
 *
 * `place` et `region` sont lus sur la destination : ils ne sont pas stockés
 * sur l'annonce, mais le front et l'application les attendent à plat.
 *
 * `tags` ajoute « verifie » quand le niveau vaut 4. C'est ici, et nulle part
 * ailleurs, que la catégorie « Séjour confirmé » se déduit de l'échelle de
 * confiance : la stocker en base créerait un second lieu de vérité qui
 * finirait par diverger.
 *
 * `photo` est la **couverture**, c'est-à-dire la première photo de la galerie.
 * Aucune colonne ne la stocke : une couverture qui n'appartiendrait pas à la
 * galerie serait un second lieu de vérité de plus.
 *
 * `perks` n'est plus une colonne de texte libre : ce sont les libellés des
 * équipements marqués `highlight` sur le pivot. Une carte ne peut donc plus
 * annoncer un équipement que la fiche ne détaille pas — c'était le défaut
 * de l'ancien tableau JSON, où « Wifi fibre » n'était qu'une chaîne que rien
 * ne reliait au reste.
 */
class ListingData extends Data
{
    /**
     * @param  array<int, string>  $tags
     * @param  array<int, string>  $perks
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly string $destination,
        public readonly string $place,
        public readonly string $region,
        public readonly string $kind,
        public readonly string $kindLabel,
        public readonly bool $wholePlace,
        public readonly ?string $summary,
        public readonly string $scene,
        public readonly ?string $photo,
        public readonly int $photoCount,
        public readonly int $guests,
        public readonly int $bedrooms,
        public readonly int $beds,
        public readonly int $bathrooms,
        public readonly ?int $surface,
        public readonly int $price,
        public readonly int $minNights,
        public readonly int $trust,
        public readonly array $tags,
        public readonly array $perks,
        public readonly int $amenityCount,
        public readonly bool $featured,
    ) {}

    public static function fromModel(Listing $listing): self
    {
        $tags = $listing->categories->pluck('key')->all();

        if ($listing->trust_level === TrustLevel::Proven) {
            $tags[] = 'verifie';
        }

        return new self(
            slug: $listing->slug,
            title: $listing->title,
            destination: $listing->destination->slug,
            place: $listing->destination->name,
            region: $listing->destination->region,
            kind: $listing->kind->value,
            kindLabel: $listing->kind->label(),
            wholePlace: $listing->kind->isWholePlace(),
            summary: $listing->summary,
            scene: $listing->scene,
            photo: $listing->photos->first()?->key,
            photoCount: $listing->photos->count(),
            guests: $listing->guests,
            bedrooms: $listing->bedrooms,
            beds: $listing->beds,
            bathrooms: $listing->bathrooms,
            surface: $listing->surface,
            price: $listing->price,
            minNights: $listing->min_nights,
            trust: $listing->trust_level->value,
            tags: array_values(array_unique($tags)),
            perks: self::highlights($listing),
            amenityCount: $listing->amenities->count(),
            featured: $listing->featured,
        );
    }

    /**
     * Les équipements mis en avant, dans l'ordre des rubriques : la carte
     * n'en montre qu'une poignée, autant que ce soit toujours le même ordre
     * d'une annonce à l'autre.
     *
     * @return array<int, string>
     */
    private static function highlights(Listing $listing): array
    {
        return $listing->amenities
            ->filter(fn ($a) => (bool) $a->pivot->highlight)
            // Clé composite plutôt que sortBy([closure, closure]) : cette
            // forme-là trie faux en Laravel 13. PHP compare les tableaux
            // élément par élément, le résultat est le tri à deux niveaux
            // attendu.
            ->sortBy(fn ($a) => [$a->group->position(), $a->position])
            ->pluck('label')
            ->values()
            ->all();
    }
}
