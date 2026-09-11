<?php

namespace App\Services\Office;

use App\Enums\AmenityGroup;
use App\Enums\ClimateZone;
use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\Amenity;
use App\Models\Category;
use App\Models\Destination;
use App\Models\Listing;
use App\Models\Owner;
use App\Models\Photo;
use App\Services\OwnerListingService;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\DB;

/**
 * Ce que reçoivent les écrans de contenu du back-office. Tout à plat, comme
 * `OfficeReadService` : aucun modèle n'arrive au front.
 */
class OfficeContentReadService
{
    /**
     * Les illustrations de repli d'une destination (`SceneArt`). Les clés sont
     * celles du composant ; un test vérifie qu'elles y existent toutes.
     */
    public const SCENES = [
        'lagoon' => 'Lagon', 'beach' => 'Plage', 'sunset' => 'Couchant',
        'lake' => 'Lac', 'highland' => 'Hautes terres', 'forest' => 'Forêt',
    ];

    /**
     * Les pictogrammes du rail de catégories (`CategoryRail`). Mêmes clés que
     * le composant — un test les compare, sinon une catégorie choisirait un
     * dessin qui n'existe pas et retomberait sur l'étincelle.
     */
    public const ICONES_CATEGORIES = [
        'sparkle' => 'Étincelle', 'wave' => 'Vague', 'drop' => 'Goutte', 'peak' => 'Sommet',
        'leaf' => 'Feuille', 'city' => 'Ville', 'group' => 'Groupe', 'check' => 'Coche',
    ];

    public function __construct(
        private OwnerListingService $annonces,
        private SettingsService $reglages,
    ) {}

    // ── Annonces ────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function editionAnnonce(?Listing $listing, ?Owner $owner = null): array
    {
        $owner ??= $listing?->owner;

        return [
            'annonce' => $listing ? [
                ...$this->annonces->pourEdition($listing->load(['amenities', 'photos', 'categories'])),
                'id' => $listing->id,
                'featured' => (bool) $listing->featured,
                'categories' => $listing->categories->pluck('id')->all(),
                'isDemo' => (bool) $listing->is_demo,
                'publicUrl' => rtrim((string) config('app.url'), '/').'/logements/'.$listing->slug,
            ] : null,
            'proprietaire' => $owner ? ['id' => $owner->id, 'name' => $owner->name] : null,
            'vocabulaire' => [
                ...$this->annonces->vocabulaire(),
                'categories' => Category::query()
                    ->whereNotIn('key', OfficeContentService::CATEGORIES_STRUCTURELLES)
                    ->orderBy('position')
                    ->get(['id', 'label', 'sponsored'])
                    ->map(fn (Category $c) => ['id' => $c->id, 'label' => $c->label, 'sponsored' => $c->sponsored])
                    ->all(),
            ],
        ];
    }

    // ── Destinations ────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function destinations(): array
    {
        return [
            'destinations' => Destination::query()
                ->with('photo')
                ->withCount('listings')
                ->orderBy('name')
                ->get()
                ->map(fn (Destination $d) => [
                    'id' => $d->id,
                    'slug' => $d->slug,
                    'name' => $d->name,
                    'region' => $d->region,
                    'tagline' => $d->tagline,
                    'zone' => $d->climate_zone?->label(),
                    'featured' => (bool) $d->featured,
                    'listings' => $d->listings_count,
                    'photo' => $d->photo ? $this->photo($d->photo) : null,
                    'acces' => (bool) ($d->airport_code || $d->road_route || $d->road_note),
                ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    public function destination(?Destination $d): array
    {
        $galerie = $d ? $d->galerie()->get() : collect();
        $dansUneGalerie = DB::table('destination_photo')
            ->join('destinations', 'destinations.id', '=', 'destination_photo.destination_id')
            ->pluck('destinations.name', 'destination_photo.photo_id');

        $fiche = fn (Photo $p) => [
            ...$this->photo($p),
            'id' => $p->id,
            'author' => $p->author,
            'licence' => $p->licence,
            'televersee' => $p->folder === 'destinations',
            'utilisee' => $dansUneGalerie[$p->id] ?? null,
        ];

        return [
            'destination' => $d ? [
                'id' => $d->id,
                'slug' => $d->slug,
                'publicUrl' => rtrim((string) config('app.url'), '/').'/destinations/'.$d->slug,
                'listings' => $d->listings()->count(),
                ...$d->only(['name', 'region', 'tagline', 'scene', 'airport_code', 'airport_name',
                    'flight_from_tana', 'road_route', 'road_km', 'road_hours', 'road_note']),
                'climate_zone' => $d->climate_zone?->value,
                'featured' => (bool) $d->featured,
            ] : null,
            // La galerie, dans l'ordre : la première est la couverture.
            'galerie' => $galerie->map($fiche)->values()->all(),
            'zones' => collect(ClimateZone::ordered())->map(fn (ClimateZone $z) => ['value' => $z->value, 'label' => $z->label()])->all(),
            'scenes' => collect(self::SCENES)->map(fn ($label, $cle) => ['value' => $cle, 'label' => $label])->values()->all(),
            // La photothèque des lieux, moins ce qui est déjà dans la galerie :
            // seulement Commons et les photos de l'équipe, jamais une image
            // générée — une destination est un lieu réel.
            'photos' => Photo::query()
                ->whereIn('folder', ['lieux', 'destinations'])
                ->where('is_ai', false)
                // Les `an-` illustrent les annonces de démonstration et
                // disparaîtront avec elles.
                ->where('key', 'not like', 'an-%')
                ->whereNotIn('id', $galerie->pluck('id'))
                ->orderByRaw("case when folder = 'destinations' then 0 else 1 end")
                ->orderByDesc('id')
                ->get()
                ->map($fiche)
                ->all(),
            'licences' => collect(OfficeContentService::LICENCES)->map(fn ($l, $cle) => ['value' => $cle, 'label' => $l['label']])->values()->all(),
        ];
    }

    // ── Catégories ──────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function categories(): array
    {
        return [
            'categories' => Category::query()
                ->withCount('listings')
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->map(fn (Category $c) => [
                    'id' => $c->id,
                    'key' => $c->key,
                    'label' => $c->label,
                    'icon' => $c->icon,
                    'sponsored' => (bool) $c->sponsored,
                    'structurelle' => in_array($c->key, OfficeContentService::CATEGORIES_STRUCTURELLES, true),
                    'listings' => match ($c->key) {
                        OfficeContentService::CATEGORIE_DEDUITE => Listing::query()->where('trust_level', 4)->count(),
                        'all' => Listing::query()->count(),
                        default => $c->listings_count,
                    },
                ])->all(),
            'icones' => collect(self::ICONES_CATEGORIES)->map(fn ($label, $cle) => ['value' => $cle, 'label' => $label])->values()->all(),
        ];
    }

    // ── Équipements ─────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function equipements(): array
    {
        $tous = Amenity::query()->withCount('listings')->orderBy('position')->orderBy('id')->get()->groupBy(fn (Amenity $a) => $a->group->value);

        return [
            'rubriques' => collect(AmenityGroup::cases())->map(fn (AmenityGroup $g) => [
                'value' => $g->value,
                'label' => $g->label(),
                'equipements' => ($tous[$g->value] ?? collect())->map(fn (Amenity $a) => [
                    'id' => $a->id,
                    'key' => $a->key,
                    'label' => $a->label,
                    'icon' => $a->icon,
                    'group' => $a->group->value,
                    'filterable' => (bool) $a->filterable,
                    'listings' => $a->listings_count,
                ])->values()->all(),
            ])->all(),
            // Seuls les pictogrammes déjà dessinés : un nom inventé n'a pas de tracé.
            'icones' => Amenity::query()->distinct()->orderBy('icon')->pluck('icon')->push('dot')->unique()->values()->all(),
            'total' => Amenity::query()->count(),
        ];
    }

    // ── Réglages ────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function reglages(): array
    {
        $qui = fn (?array $t) => $t && $t['admin_id'] ? Admin::query()->find($t['admin_id'])?->name : null;
        $trace = fn (string $cle) => ($t = $this->reglages->trace($cle)) ? ['at' => $t['at'], 'par' => $qui($t)] : null;

        return [
            'taux' => [
                'valeur' => $this->reglages->tauxEuro(),
                'date' => $this->reglages->tauxEuroReleveLe(),
                'trace' => $trace(SettingsService::TAUX_EURO),
                'source' => $this->reglages->trace(SettingsService::TAUX_EURO) ? 'back-office' : 'configuration',
            ],
            'commission' => [
                'pourcent' => round($this->reglages->commission() * 100, 2),
                'trace' => $trace(SettingsService::COMMISSION),
                'source' => $this->reglages->trace(SettingsService::COMMISSION) ? 'back-office' : 'configuration',
            ],
            'historique' => AdminAction::query()
                ->where('kind', 'setting_changed')
                ->latest('id')
                ->limit(10)
                ->get()
                ->map(fn (AdminAction $a) => AdminJournal::ligne($a))
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function photo(Photo $p): array
    {
        return ['key' => $p->key, 'folder' => $p->folder, 'width' => $p->width, 'caption' => $p->caption];
    }
}
