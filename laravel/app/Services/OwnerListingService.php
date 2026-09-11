<?php

namespace App\Services;

use App\Contracts\Listings\ListingDrafting;
use App\Contracts\Repositories\ListingDraftRepositoryInterface;
use App\Data\Listings\AmenityChoiceGroupData;
use App\Data\Listings\AmenityOptionData;
use App\Data\Listings\DestinationChoiceData;
use App\Data\Listings\ListingFormData;
use App\Data\Listings\ListingVocabularyData;
use App\Data\OptionData;
use App\DTOs\Listings\AmenityChoiceDto;
use App\DTOs\Listings\ListingFicheDto;
use App\Enums\AmenityGroup;
use App\Enums\PropertyType;
use App\Models\Amenity;
use App\Models\Destination;
use App\Models\Listing;
use App\Models\Owner;
use App\Services\Support\UniqueSlug;
use Illuminate\Support\Str;

/**
 * La saisie d'une annonce par son propriétaire.
 *
 * **C'est lui qui connaît son logement** : le nombre de couchages, le
 * groupe électrogène, la piste en 4×4. Le faire dicter au téléphone à
 * quelqu'un de Vayla était tenable pour huit annonces, pas pour trente.
 *
 * **Mais il ne publie pas, il soumet.** Une annonce naît en `Draft`, passe en
 * `Submitted` quand elle est complète, et n'apparaît sur le site que
 * lorsque Vayla la met en `Published`. Sans ce palier, n'importe qui se
 * mettrait en ligne et « vérifié » ne voudrait plus rien dire — c'est le seul
 * actif du produit. Le `trust_level` reste attribué par Vayla, jamais déclaré :
 * il n'apparaît nulle part dans ce qu'un propriétaire peut écrire.
 *
 * **Ce qu'il peut modifier après vérification est volontairement restreint.**
 * Une annonce visitée en visio dont on pourrait changer les photos, la
 * capacité ou l'adresse ferait de la vérification un tampon sans objet : le
 * niveau porterait sur un logement qui n'existe plus. Tarif, description et
 * calendrier bougent librement ; le reste passe par une demande à Vayla.
 */
class OwnerListingService implements ListingDrafting
{
    /**
     * Ce qu'un propriétaire peut changer sur une annonce **déjà vérifiée**.
     *
     * Le tarif change avec la saison, la description se corrige, les durées de
     * séjour se règlent : rien de tout ça ne remet en cause ce que Vayla est
     * allé voir. La capacité, le type, la destination, les équipements et les
     * photos, si.
     */
    private const LIBRES_APRES_VERIFICATION = [
        'summary', 'description', 'price', 'min_nights', 'max_nights',
        'check_in_from', 'check_out_before', 'pets_allowed', 'smoking_allowed', 'events_allowed',
    ];

    public function __construct(
        private ListingDraftRepositoryInterface $annonces,
        private UniqueSlug $slugs,
    ) {}

    /** Les listes fermées dont le formulaire a besoin. */
    public function vocabulaire(): ListingVocabularyData
    {
        return new ListingVocabularyData(
            destinations: $this->annonces->destinations()->map(fn (Destination $d) => DestinationChoiceData::fromModel($d))->values()->all(),
            kinds: array_map(fn (PropertyType $t) => new OptionData($t->value, $t->label()), PropertyType::ordered()),
            // Les équipements arrivent **groupés par rubrique** : cent deux
            // cases à cocher en une seule liste sont illisibles, et le
            // propriétaire abandonne avant la moitié.
            amenityGroups: $this->equipementsParRubrique(),
        );
    }

    public function pourEdition(Listing $listing): ListingFormData
    {
        return ListingFormData::fromModel($this->annonces->pourEdition($listing));
    }

    /** Le `trust_level` n'est jamais dans la fiche : c'est Vayla qui l'attribue. */
    public function creer(Owner $owner, ListingFicheDto $fiche): Listing
    {
        return $this->annonces->creer($owner, $fiche, $this->slugs->pour(Str::slug($fiche->title) ?: 'logement', $this->annonces->slugPris(...)));
    }

    /**
     * Après vérification, seuls les champs qui ne remettent pas en cause ce
     * que Vayla est allé voir restent modifiables.
     */
    public function modifier(Listing $listing, ListingFicheDto $fiche): void
    {
        $this->annonces->modifierFiche($listing, $fiche, $listing->status->estModifiable() ? null : self::LIBRES_APRES_VERIFICATION);
    }

    /** @param  list<AmenityChoiceDto>  $choix */
    public function poserEquipements(Listing $listing, array $choix): void
    {
        $this->annonces->poserEquipements($listing, $choix);
    }

    /**
     * Envoie la fiche à Vayla si elle est complète.
     *
     * @return list<string> ce qui manque, écrit pour le propriétaire ; vide si elle est partie
     */
    public function soumettre(Listing $listing): array
    {
        $manques = $this->manques($listing);

        if ($manques === []) {
            $this->annonces->soumettre($listing);
        }

        return $manques;
    }

    /** @return list<string> */
    private function manques(Listing $listing): array
    {
        return array_keys(array_filter([
            'une phrase de présentation (au moins vingt caractères)' => Str::length((string) $listing->summary) < 20,
            'une description du logement (au moins cent vingt caractères)' => Str::length((string) $listing->description) < 120,
            'au moins trois photos' => $this->annonces->nombrePhotos($listing) < 3,
            'au moins trois équipements cochés' => $this->annonces->nombreEquipements($listing) < 3,
            'un tarif par nuit' => ! $listing->price,
        ]));
    }

    /** @return list<AmenityChoiceGroupData> les rubriques qui ont au moins un équipement */
    private function equipementsParRubrique(): array
    {
        $parGroupe = $this->annonces->equipements()->groupBy(fn (Amenity $a) => $a->group->value);

        return collect(AmenityGroup::cases())
            ->map(fn (AmenityGroup $g) => new AmenityChoiceGroupData(
                key: $g->value,
                label: $g->label(),
                amenities: $parGroupe->get($g->value, collect())->map(fn (Amenity $a) => AmenityOptionData::fromModel($a))->values()->all(),
            ))
            ->filter(fn (AmenityChoiceGroupData $g) => $g->amenities !== [])
            ->values()
            ->all();
    }
}
