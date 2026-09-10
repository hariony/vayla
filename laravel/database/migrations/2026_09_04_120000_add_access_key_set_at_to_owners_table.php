<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Depuis quand la clé d'accès en cours est en circulation.
 *
 * Une rotation sans trace est une demi-fonctionnalité : au moment de décider
 * s'il faut changer le lien d'un propriétaire, la première question est
 * « depuis combien de temps celui-ci tourne ? ». Une colonne, pas un journal
 * complet — on ne cherche pas à savoir qui s'est connecté, seulement l'âge de
 * la clé.
 *
 * Nullable, et laissée vide sur les lignes existantes : les remplir avec
 * `now()` prétendrait que ces clés ont été posées le jour de la migration.
 * « Date inconnue » est vrai ; une date fausse ne l'est pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->timestamp('access_key_set_at')->nullable()->after('access_key');
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn('access_key_set_at');
        });
    }
};
