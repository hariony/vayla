<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comment on rejoint une destination.
 *
 * C'est l'information la plus difficile à trouver quand on prépare un séjour
 * à Madagascar, et aucune plateforme de location ne la publie : on la
 * reconstitue de forum en forum. Elle décide pourtant du voyage — Tuléar est
 * à 1 h 30 d'avion ou à deux jours de route, et ce n'est pas le même séjour.
 *
 * En colonnes typées et non en paragraphe : « à moins de quatre heures de
 * Tana » doit pouvoir devenir un filtre le jour où quelqu'un le demande, et
 * un texte ne se filtre pas. `road_hours` reste une chaîne parce que la
 * vérité est une fourchette — « 3 à 4 h » — et qu'un entier laisserait
 * croire à une précision que les routes malgaches n'ont pas.
 *
 * Antananarivo n'a ni route ni vol depuis Tana : c'est le point de départ.
 * Les colonnes nullables portent ce cas sans invention.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->string('airport_code', 3)->nullable();
            $table->string('airport_name')->nullable();
            $table->string('flight_from_tana')->nullable();
            $table->string('road_route')->nullable();
            $table->unsignedSmallInteger('road_km')->nullable();
            $table->string('road_hours')->nullable();
            $table->string('road_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->dropColumn([
                'airport_code', 'airport_name', 'flight_from_tana',
                'road_route', 'road_km', 'road_hours', 'road_note',
            ]);
        });
    }
};
