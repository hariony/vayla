<?php

namespace App\Services;

use App\Data\AccessData;
use App\Data\DestinationDetailData;
use App\Data\ListingData;
use App\Enums\TrustLevel;
use App\Exceptions\DestinationNotFoundException;
use App\Models\Destination;
use App\Models\Listing;
use App\Repositories\Contracts\ListingRepositoryInterface;
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
        private DestinationService $destinations,
        private SeasonService $seasons,
        private PhotoService $photos,
    ) {}

    /** @return array<string, mixed> */
    public function show(string $slug): array
    {
        $demo = (bool) config('vayla.demo');

        $destination = Destination::query()->with(['photo', 'galerie'])->where('slug', $slug)->first()
            ?? throw new DestinationNotFoundException($slug);

        $rows = $this->listings->forDestination($slug, $demo);

        return [
            'fiche' => new DestinationDetailData(
                destination: $this->destinations->show($slug, $demo),
                access: AccessData::fromModel($destination),
                listings: $rows->map(fn ($l) => ListingData::fromModel($l))->values()->all(),
                trust: $this->trust($rows),
                season: $this->seasons->payload($destination),
                prices: [
                    'min' => $rows->min('price'),
                    'max' => $rows->max('price'),
                ],
            ),
            'destinations' => $this->destinations->atlas($demo),
            'demo' => $demo,
            // La galerie, dans l'ordre choisi au back-office : la première est
            // la couverture, celle de l'atlas.
            'galerie' => $destination->galerie->pluck('key')->values()->all(),
            'photos' => $this->photos->map(),
            'credits' => $this->photos->credits(),
        ];
    }

    /** @return array<string, mixed> */
    public function index(): array
    {
        $demo = (bool) config('vayla.demo');

        return [
            'destinations' => $this->destinations->atlas($demo),
            'demo' => $demo,
            'photos' => $this->photos->map(),
            'credits' => $this->photos->credits(),
        ];
    }

    /**
     * La répartition par barreau. Les niveaux à zéro sortent aussi : une
     * échelle amputée de ses barreaux vides ne se lit plus comme une échelle.
     *
     * @param  Collection<int, Listing>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function trust($rows): array
    {
        return collect(TrustLevel::ladder())
            ->map(fn (TrustLevel $level) => [
                'level' => $level->value,
                'key' => $level->key(),
                'name' => $level->label(),
                'count' => $rows->where('trust_level', $level)->count(),
            ])
            ->values()
            ->all();
    }
}
