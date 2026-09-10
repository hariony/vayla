<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le motif d'une période devient un vocabulaire fermé (`App\Enums\BlockReason`).
 *
 * La colonne ne change pas de type — elle reste une chaîne courte — mais son
 * contenu devient une valeur d'enum, et le modèle la lit désormais par un
 * cast. Les périodes déjà en base portent du texte libre issu du seeder de
 * démonstration (« Vacances scolaires », « Séjour confirmé »…) : les laisser
 * ferait **lever** le cast à la première lecture du calendrier, c'est-à-dire
 * sur la fiche publique. On les ramène donc sur le motif le plus proche.
 *
 * Les libellés sont écrits en dur ici plutôt qu'importés de l'enum : une
 * migration doit continuer à rejouer à l'identique le jour où l'enum change.
 */
return new class extends Migration
{
    private const CORRESPONDANCES = [
        'Séjour en cours' => 'loue_direct',
        'Séjour confirmé' => 'loue_direct',
        'Séjour familial' => 'loue_direct',
        'Séjour de groupe' => 'loue_direct',
        'Séjour long' => 'loue_direct',
        'Week-end long' => 'loue_direct',
        'Haute saison' => 'loue_direct',
        'Vacances scolaires' => 'occupe',
    ];

    public function up(): void
    {
        foreach (self::CORRESPONDANCES as $texte => $valeur) {
            DB::table('unavailabilities')->where('reason', $texte)->update(['reason' => $valeur]);
        }

        // Tout ce qui n'a pas été reconnu tombe sur « Autre raison » plutôt
        // que de casser la lecture : un motif est une note, pas une décision.
        DB::table('unavailabilities')
            ->whereNotNull('reason')
            ->whereNotIn('reason', ['loue_direct', 'occupe', 'travaux', 'ferme', 'autre'])
            ->update(['reason' => 'autre']);
    }

    public function down(): void
    {
        // Le texte libre d'origine n'est pas reconstituable : on repart à vide.
        DB::table('unavailabilities')->update(['reason' => null]);
    }
};
