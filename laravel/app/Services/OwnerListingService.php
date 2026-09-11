<?php

namespace App\Services;

use App\Contracts\Listings\ListingDrafting;
use App\Enums\AmenityGroup;
use App\Enums\ListingStatus;
use App\Enums\PropertyType;
use App\Models\Amenity;
use App\Models\Destination;
use App\Models\Listing;
use App\Models\Owner;
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

    /** Les listes fermées dont le formulaire a besoin. @return array<string, mixed> */
    public function vocabulaire(): array
    {
        return [
            'destinations' => Destination::query()
                ->orderBy('name')
                ->get(['id', 'name', 'region'])
                ->map(fn (Destination $d) => [
                    'id' => $d->id,
                    'label' => "{$d->name} — {$d->region}",
                ])->all(),

            'kinds' => array_map(
                fn (PropertyType $t) => ['value' => $t->value, 'label' => $t->label()],
                PropertyType::ordered(),
            ),

            // Les équipements arrivent **groupés par rubrique** : cent deux
            // cases à cocher en une seule liste sont illisibles, et le
            // propriétaire abandonne avant la moitié.
            'amenityGroups' => $this->equipementsParRubrique(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function equipementsParRubrique(): array
    {
        $parGroupe = Amenity::query()->orderBy('position')->get()->groupBy('group');

        return collect(AmenityGroup::cases())
            ->map(fn (AmenityGroup $g) => [
                'key' => $g->value,
                'label' => $g->label(),
                'amenities' => ($parGroupe[$g->value] ?? collect())
                    ->map(fn (Amenity $a) => [
                        'id' => $a->id,
                        'key' => $a->key,
                        'label' => $a->label,
                        'icon' => $a->icon,
                    ])->values()->all(),
            ])
            ->filter(fn (array $g) => $g['amenities'] !== [])
            ->values()
            ->all();
    }

    /** La fiche telle que le formulaire la reçoit. @return array<string, mixed> */
    public function pourEdition(Listing $listing): array
    {
        return [
            'slug' => $listing->slug,
            'status' => $listing->status->value,
            'statusLabel' => $listing->status->label(),
            'consigne' => $listing->status->consigne(),
            'reviewNote' => $listing->review_note,
            'modifiable' => $listing->status->estModifiable(),
            'trust' => $listing->trust_level->value,
            'trustName' => $listing->trust_level->label(),

            'title' => $listing->title,
            'destination_id' => $listing->destination_id,
            'kind' => $listing->kind->value,
            'summary' => $listing->summary,
            'description' => $listing->description,

            'guests' => $listing->guests,
            'bedrooms' => $listing->bedrooms,
            'beds' => $listing->beds,
            'bathrooms' => $listing->bathrooms,
            'surface' => $listing->surface,

            'price' => $listing->price,
            'min_nights' => $listing->min_nights,
            'max_nights' => $listing->max_nights,
            'check_in_from' => substr((string) $listing->check_in_from, 0, 5),
            'check_out_before' => substr((string) $listing->check_out_before, 0, 5),
            'pets_allowed' => $listing->pets_allowed,
            'smoking_allowed' => $listing->smoking_allowed,
            'events_allowed' => $listing->events_allowed,

            'amenities' => $listing->amenities->map(fn (Amenity $a) => [
                'id' => $a->id,
                'highlight' => (bool) $a->pivot->highlight,
            ])->all(),

            'photos' => $listing->photos->map(fn ($p) => [
                'id' => $p->id,
                'key' => $p->key,
                'folder' => $p->folder,
                'width' => $p->width,
                'caption' => $p->caption,
                'position' => $p->pivot->position,
            ])->sortBy('position')->values()->all(),
        ];
    }

    /**
     * Crée une annonce vide, en brouillon.
     *
     * Le slug est tiré du titre et rendu unique : c'est l'adresse publique de
     * la fiche, elle doit être stable une fois posée — on ne la recalcule
     * donc jamais à la modification, même si le titre change. Un lien partagé
     * qui casse est un voyageur perdu.
     */
    public function creer(Owner $owner, array $donnees): Listing
    {
        $listing = new Listing($donnees);
        $listing->owner_id = $owner->id;
        $listing->slug = $this->slugUnique($donnees['title']);
        $listing->status = ListingStatus::Draft;
        // Le niveau de confiance n'est jamais dans `$donnees` : c'est Vayla
        // qui l'attribue, et une annonce neuve part de « déclarée ».
        $listing->trust_level = 1;
        $listing->save();

        return $listing;
    }

    public function modifier(Listing $listing, array $donnees): void
    {
        // Après vérification, seuls les champs qui ne remettent pas en cause
        // ce que Vayla est allé voir restent modifiables.
        if (! $listing->status->estModifiable()) {
            $donnees = array_intersect_key($donnees, array_flip(self::LIBRES_APRES_VERIFICATION));
        }

        $listing->fill($donnees)->save();
    }

    /**
     * Les équipements cochés, avec ceux mis en avant.
     *
     * `perks` — les trois arguments affichés sur la carte — sont **dérivés**
     * de `highlight`, jamais saisis à part : une carte ne peut donc pas vanter
     * un équipement que la fiche ne détaille pas.
     *
     * @param  array<int, array{id: int, highlight: bool}>  $choix
     */
    public function poserEquipements(Listing $listing, array $choix): void
    {
        $listing->amenities()->sync(
            collect($choix)
                ->mapWithKeys(fn (array $c) => [(int) $c['id'] => ['highlight' => (bool) ($c['highlight'] ?? false)]])
                ->all()
        );
    }

    /**
     * Envoie l'annonce à la vérification.
     *
     * On refuse une fiche incomplète plutôt que de la laisser partir : Vayla
     * appellerait pour réclamer ce qui manque, et le propriétaire aurait
     * l'impression d'avoir travaillé pour rien. Le message dit **ce qui**
     * manque, pas « formulaire invalide ».
     *
     * @return array<int, string> Ce qui manque. Vide = c'est parti.
     */
    public function soumettre(Listing $listing): array
    {
        $manques = [];

        if (Str::length((string) $listing->summary) < 20) {
            $manques[] = 'une phrase de présentation (au moins vingt caractères)';
        }
        if (Str::length((string) $listing->description) < 120) {
            $manques[] = 'une description du logement (au moins cent vingt caractères)';
        }
        if ($listing->photos()->count() < 3) {
            $manques[] = 'au moins trois photos';
        }
        if ($listing->amenities()->count() < 3) {
            $manques[] = 'au moins trois équipements cochés';
        }
        if (! $listing->price) {
            $manques[] = 'un tarif par nuit';
        }

        if ($manques === []) {
            $listing->status = ListingStatus::Submitted;
            // Le motif du renvoi a servi : la fiche repart, et le garder
            // afficherait une demande à laquelle il a déjà répondu.
            $listing->review_note = null;
            $listing->save();
        }

        return $manques;
    }

    private function slugUnique(string $titre): string
    {
        $base = Str::slug($titre) ?: 'logement';
        $slug = $base;
        $n = 2;

        while (Listing::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$n}";
            $n++;
        }

        return $slug;
    }
}
