<?php

namespace App\Services\Photos;

use App\Data\Photos\PhotoUsageData;
use App\Enums\PhotoProvenance;
use App\Models\Photo;
use Illuminate\Support\Str;

/**
 * **Pourquoi une photo ne se supprime pas** — ou `null` si elle le peut.
 * Écrit une fois : l'écran l'affiche à la place du bouton, et
 * `PhotoRemover` le lève en refus. Deux copies auraient fini par ne pas dire
 * la même chose.
 */
final class PhotoRemovalPolicy
{
    /** @param  array<int, PhotoUsageData>  $usages */
    public function raison(Photo $photo, array $usages): ?string
    {
        if ($raison = PhotoProvenance::de($photo)->pourquoiPasSupprimer()) {
            return $raison;
        }

        if ($usages === []) {
            return null;
        }

        $galeries = collect($usages)
            ->map(fn (PhotoUsageData $u) => '« '.Str::before($u->label, ' — ').' »')
            ->unique()
            ->implode(', ');

        return "Elle illustre encore {$galeries} : retirez-la de la galerie d’abord.";
    }
}
