<?php

namespace App\Repositories;

use App\Data\ListingFiltreData;
use App\Data\SejourData;
use App\Enums\BookingStatus;
use App\Enums\ListingSort;
use App\Enums\ListingStatus;
use App\Enums\TrustLevel;
use App\Models\Listing;
use App\Repositories\Contracts\ListingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ListingRepository implements ListingRepositoryInterface
{
    public function published(bool $includeDemo): Collection
    {
        return $this->base($includeDemo)
            ->orderByDesc('featured')
            ->orderByDesc('trust_level')
            ->orderBy('id')
            ->get();
    }

    public function paginate(ListingFiltreData $filtre, bool $includeDemo): LengthAwarePaginator
    {
        return $this->base($includeDemo)
            ->when(
                $filtre->destination,
                fn (Builder $q, string $slug) => $q->whereHas(
                    'destination',
                    fn (Builder $d) => $d->where('slug', $slug)
                )
            )
            // « Séjour confirmé » n'est pas une étiquette stockée : c'est le
            // niveau 4 de l'échelle. Un seul lieu de vérité, ici comme dans
            // ListingData.
            ->when(
                $filtre->category === 'verifie',
                fn (Builder $q) => $q->where('trust_level', TrustLevel::Proven->value)
            )
            ->when(
                $filtre->category && ! in_array($filtre->category, ['all', 'verifie'], true),
                fn (Builder $q) => $q->whereHas(
                    'categories',
                    fn (Builder $c) => $c->where('key', $filtre->category)
                )
            )
            ->when($filtre->guests, fn (Builder $q, int $n) => $q->where('guests', '>=', $n))
            // Les nuits demandées. Trois conditions, et les trois comptent :
            // aucune période déclarée ne les couvre, aucune réservation
            // bloquante ne les couvre, et le logement accepte un séjour de
            // cette durée. Afficher une annonce qu'on ne peut pas réserver
            // aux dates demandées est exactement le genre de promesse creuse
            // que ce produit refuse.
            ->when(
                $filtre->sejour,
                fn (Builder $q, SejourData $sejour) => $q
                    ->whereDoesntHave(
                        'unavailabilities',
                        fn (Builder $u) => $u
                            ->where('starts_on', '<=', $sejour->derniereNuit())
                            ->where('ends_on', '>=', $sejour->arrival)
                    )
                    // `departure` est exclue : le jour du départ d'un
                    // occupant est réservable par le suivant. La comparer
                    // avec `>=` retirerait une nuit vendable par séjour.
                    ->whereDoesntHave(
                        'bookings',
                        fn (Builder $b) => $b
                            ->whereIn('status', array_column(BookingStatus::blocking(), 'value'))
                            ->where(fn (Builder $vivante) => $vivante
                                ->whereNull('hold_expires_at')
                                ->orWhere('hold_expires_at', '>', now()))
                            ->where('arrival', '<=', $sejour->derniereNuit())
                            ->where('departure', '>', $sejour->arrival)
                    )
                    ->where('min_nights', '<=', $sejour->nights)
                    ->where(fn (Builder $max) => $max
                        ->whereNull('max_nights')
                        ->orWhere('max_nights', '>=', $sejour->nights))
            )
            ->when($filtre->minTrust, fn (Builder $q, int $n) => $q->where('trust_level', '>=', $n))
            ->when($filtre->kind, fn (Builder $q, string $k) => $q->where('kind', $k))
            ->when($filtre->maxPrice, fn (Builder $q, int $p) => $q->where('price', '<=', $p))
            // Un whereHas par équipement demandé : cocher deux cases pose
            // deux conditions, pas une alternative. Un seul whereHas avec
            // whereIn ramènerait les logements qui n'en ont qu'un.
            ->when(
                $filtre->amenities !== [],
                function (Builder $q) use ($filtre) {
                    foreach ($filtre->amenities as $key) {
                        $q->whereHas('amenities', fn (Builder $a) => $a->where('key', $key));
                    }
                }
            )
            ->tap(fn (Builder $q) => $this->applySort($q, $filtre->sort))
            ->paginate(perPage: $filtre->perPage, page: $filtre->page);
    }

    public function findBySlug(string $slug, bool $includeDemo): ?Listing
    {
        // Les indisponibilités ne sont chargées que sur la fiche : la grille
        // n'affiche pas de calendrier, et douze mois de périodes pour vingt
        // annonces seraient de la charge utile pure perte.
        return $this->base($includeDemo)
            ->with(['unavailabilities', 'confirmations', 'bookings', 'owner'])
            ->where('slug', $slug)
            ->first();
    }

    public function similar(Listing $listing, int $limit, bool $includeDemo): Collection
    {
        return $this->base($includeDemo)
            ->where('destination_id', $listing->destination_id)
            ->whereKeyNot($listing->getKey())
            ->orderByDesc('trust_level')
            ->orderByDesc('featured')
            ->limit($limit)
            ->get();
    }

    public function forDestination(string $slug, bool $includeDemo): Collection
    {
        return $this->base($includeDemo)
            ->whereHas('destination', fn (Builder $d) => $d->where('slug', $slug))
            ->orderByDesc('featured')
            ->orderByDesc('trust_level')
            ->orderBy('id')
            ->get();
    }

    public function facets(bool $includeDemo): array
    {
        // Une seule requête d'agrégat : le panneau de filtres n'a pas à
        // charger les annonces pour connaître ses bornes.
        $bornes = $this->base($includeDemo)
            ->reorder()
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price, COUNT(*) as total')
            ->toBase()
            ->first();

        // `toBase()` est indispensable : sur un Builder Eloquent, `pluck`
        // applique les casts et renvoie des PropertyType, pas des chaînes —
        // la comparaison stricte côté service échouait en silence et le
        // filtre par type ne s'affichait jamais.
        $kinds = $this->base($includeDemo)
            ->reorder()
            ->select('kind')
            ->distinct()
            ->orderBy('kind')
            ->toBase()
            ->pluck('kind')
            ->all();

        return [
            'kinds' => $kinds,
            'priceMin' => (int) ($bornes->min_price ?? 0),
            'priceMax' => (int) ($bornes->max_price ?? 0),
            'total' => (int) ($bornes->total ?? 0),
        ];
    }

    /**
     * Le tri par défaut range la vérification avant tout le reste : sur un
     * site dont la promesse est là, c'est l'ordre attendu. Les autres tris
     * gardent `trust_level` en départage — à prix égal, le logement vérifié
     * passe devant.
     */
    private function applySort(Builder $query, ListingSort $sort): void
    {
        match ($sort) {
            ListingSort::Confiance => $query
                ->orderByDesc('featured')
                ->orderByDesc('trust_level'),
            ListingSort::PrixCroissant => $query
                ->orderBy('price')
                ->orderByDesc('trust_level'),
            ListingSort::PrixDecroissant => $query
                ->orderByDesc('price')
                ->orderByDesc('trust_level'),
            ListingSort::Capacite => $query
                ->orderByDesc('guests')
                ->orderByDesc('trust_level'),
        };

        // Départage stable : sans lui, deux annonces au même prix peuvent
        // changer de place d'une page à l'autre et l'une disparaît.
        $query->orderBy('id');
    }

    /** Socle commun : seules les annonces publiées sortent d'ici. */
    private function base(bool $includeDemo): Builder
    {
        return Listing::query()
            ->with(['destination.photo', 'photos', 'categories', 'amenities'])
            ->where('status', ListingStatus::Published->value)
            ->when(! $includeDemo, fn (Builder $q) => $q->where('is_demo', false));
    }
}
