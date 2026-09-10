<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les périodes où un logement n'est pas libre.
 *
 * Le sens de la table est **l'absence** : une date est disponible tant
 * qu'aucune ligne ne la couvre. Une table `availabilities` aurait obligé le
 * propriétaire à déclarer chaque jour libre pour les trois cents prochains,
 * et un oubli aurait fermé son calendrier au lieu de l'ouvrir.
 *
 * Vayla ne prend pas de réservation : ces périodes sont **déclarées par le
 * propriétaire**, pas déduites de paiements. Le calendrier sert au voyageur à
 * choisir ses dates avant de déposer sa demande — et ces dates sont ensuite
 * ce qui déclenche la confirmation de séjour.
 *
 * `ends_on` est la dernière nuit occupée, pas le départ : un séjour du 12 au
 * 15 occupe les nuits 12, 13, 14. Confondre les deux ferait perdre une nuit
 * réservable à chaque période.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unavailabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['listing_id', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unavailabilities');
    }
};
