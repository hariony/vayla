<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La largeur réelle du plus grand fichier disponible pour cette photo.
 *
 * Chaque photographie est déclinée en 800, 1600 et 3200 px — mais seulement
 * jusqu'à la résolution que l'original de Commons porte vraiment. Sans cette
 * colonne, le `srcset` promettrait des pixels qui n'existent pas et le
 * navigateur téléchargerait un fichier absent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->unsignedSmallInteger('width')->default(1600)->after('key');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('width');
        });
    }
};
