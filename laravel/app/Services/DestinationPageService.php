<?php

namespace App\Services;

use App\Contracts\Repositories\DestinationRepositoryInterface;
use App\Contracts\Repositories\ListingRepositoryInterface;
use App\Data\AccessData;
use App\Data\DestinationDetailData;
use App\Data\ListingData;
use App\Data\Pages\AtlasPageData;
use App\Data\Pages\DestinationPageData;
use App\Data\PriceRangeData;
use App\Data\TrustCountData;
use App\Enums\TrustLevel;
use App\Exceptions\DestinationNotFoundException;
use App\Services\Support\DemoMode;
use Illuminate\Support\Collection;

/**
 * Orchestrateur de la page d'une destination.
 *
 * La règle qui tient cette page : **aucun chiffre affiché n'est saisi**. Le
 * nombre de logements, la répartition par niveau de confiance et les bornes
 * de prix sont tous calculés depuis les annonces. Une destination qui n'en a
 * aucune affiche zéro, et c'est le cas de cinq sur onze — le dire est plus
 * utile que de le maquiller.
 *
 * La répartition est publiée **barreau par barreau**, jamais en moyenne :
 * « 6 logements, dont 2 au niveau séjour confirmé » informe ; « 6 logements,
 * niveau moyen 2,8 » ne veut rien dire.
 */
class DestinationPageService
{
    public function __construct(
        private ListingRepositoryInterface $listings,
        private DestinationRepositoryInterface $lieux,
        private DestinationService $destinations,
        private SeasonService $seasons,
        private PhotoService $photos,
        private DemoMode $demo,
    ) {}

    public function show(string $slug): DestinationPageData
    {
        $destination = $this->lieux->findWithGallery($slug) ?? throw new DestinationNotFoundException($slug);
        $rows = $this->listings->forDestination($slug, $this->demo->actif());

        return new DestinationPageData(
            fiche: new DestinationDetailData(
                destination: $this->destinations->show($slug),
                access: AccessData::fromModel($destination),
                listings: $rows->map(fn ($l) => ListingData::fromModel($l))->values()->all(),
                trust: $this->trust($rows),
                season: $this->seasons->saison($destination),
                prices: new PriceRangeData($rows->min('price'), $rows->max('price')),
            ),
            destinations: $this->destinations->atlas(),
            demo: $this->demo->actif(),
            // La galerie, dans l'ordre choisi au back-office : la première est
            // la couverture, celle de l'atlas.
            galerie: $destination->galerie->pluck('key')->values()->all(),
            photos: $this->photos->map(),
            credits: $this->photos->credits(),
        );
    }

    public function index(): AtlasPageData
    {
        return new AtlasPageData(
            destinations: $this->destinations->atlas(),
            demo: $this->demo->actif(),
            photos: $this->photos->map(),
            credits: $this->photos->credits(),
        );
    }

    /**
     * Les quatre barreaux, zéro compris : une échelle amputée de ses barreaux
     * vides ne se lit plus comme une échelle.
     *
     * @return list<TrustCountData>
     */
    private function trust(Collection $rows): array
    {
        return array_map(fn (TrustLevel $level) => new TrustCountData(
            level: $level->value,
            key: $level->key(),
            name: $level->label(),
            count: $rows->where('trust_level', $level)->count(),
        ), TrustLevel::ladder());
    }
}
