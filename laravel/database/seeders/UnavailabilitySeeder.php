<?php

namespace Database\Seeders;

use App\Enums\BlockReason;
use App\Models\Listing;
use App\Models\Unavailability;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Des périodes occupées, pour que le calendrier de démonstration ait
 * quelque chose à montrer.
 *
 * Les dates sont **relatives à aujourd'hui**, jamais écrites en dur : un
 * calendrier semé avec des dates fixes est entièrement dans le passé six
 * mois plus tard, et la démonstration montre alors un logement libre toute
 * l'année. `updateOrCreate` sur le couple (annonce, début) garde le seeder
 * idempotent.
 *
 * Les jeux sont inégaux à dessein : la villa vedette est très prise, la case
 * d'Ifaty presque libre. Un calendrier uniforme ne prouverait pas que le
 * composant sait afficher une haute saison.
 *
 * `ends_on` est la dernière nuit occupée, pas le départ.
 *
 * Les motifs sont ceux du formulaire du propriétaire (`BlockReason`) et non
 * du texte libre : la démonstration doit montrer ce que l'écran produit
 * réellement, sinon elle valide une mise en page que personne ne verra.
 */
class UnavailabilitySeeder extends Seeder
{
    public function run(): void
    {
        $listings = Listing::query()->pluck('id', 'slug');
        $concernees = $listings->only(array_keys($this->periods()));

        // Les dates sont relatives à aujourd'hui : `updateOrCreate` sur
        // (annonce, début) ne retrouvait donc plus la ligne de la veille et
        // en créait une seconde. Rejoué un autre jour, le seeder doublait le
        // calendrier — et une nuit bloquée deux fois est invisible à l'œil.
        // Le seeder possède entièrement les périodes de ces annonces : il les
        // reprend à zéro plutôt que d'essayer de les reconnaître.
        Unavailability::query()->whereIn('listing_id', $concernees->values())->delete();

        foreach ($this->periods() as $slug => $periods) {
            foreach ($periods as [$debut, $duree, $raison]) {
                $start = Carbon::today()->addDays($debut);

                Unavailability::create([
                    'listing_id' => $listings[$slug],
                    'starts_on' => $start->toDateString(),
                    'ends_on' => $start->copy()->addDays($duree - 1)->toDateString(),
                    'reason' => $raison,
                ]);
            }
        }
    }

    /**
     * [jours à partir d'aujourd'hui, nombre de nuits, motif]
     *
     * @return array<string, array<int, array{0: int, 1: int, 2: BlockReason}>>
     */
    private function periods(): array
    {
        return [
            'villa-ambatoloaka' => [
                [4, 6, BlockReason::LoueDirect],
                [21, 11, BlockReason::Occupe],
                [58, 8, BlockReason::LoueDirect],
                [96, 14, BlockReason::LoueDirect],
            ],
            'bungalow-madirokely' => [
                [9, 4, BlockReason::LoueDirect],
                [40, 6, BlockReason::LoueDirect],
                [102, 5, BlockReason::LoueDirect],
            ],
            'maison-itasy' => [
                [12, 3, BlockReason::Occupe],
                [33, 4, BlockReason::LoueDirect],
                [74, 9, BlockReason::Occupe],
            ],
            'front-de-mer-amborovy' => [
                [6, 5, BlockReason::LoueDirect],
                [47, 7, BlockReason::LoueDirect],
            ],
            'villa-coloniale-antsirabe' => [
                [17, 4, BlockReason::LoueDirect],
                [63, 10, BlockReason::Occupe],
            ],
            'case-ifaty' => [
                [29, 3, BlockReason::LoueDirect],
            ],
            'lodge-andasibe' => [
                [3, 4, BlockReason::LoueDirect],
                [26, 5, BlockReason::LoueDirect],
                [55, 6, BlockReason::LoueDirect],
                [88, 7, BlockReason::LoueDirect],
            ],
            'studio-thermal' => [
                [14, 12, BlockReason::Ferme],
                [51, 20, BlockReason::Ferme],
            ],
        ];
    }
}
