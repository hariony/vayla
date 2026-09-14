<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * **D'où vient chaque propriétaire.**
 *
 * La collecte des logements passe par des publicités et des messages dans les
 * groupes : chacun porte son lien (`/proprietaires?source=facebook-nosybe`).
 * Sans cette colonne, on paierait des campagnes sans jamais savoir laquelle a
 * fait inscrire quelqu'un. Un compte saisi par l'équipe porte `equipe`.
 *
 * Nullable : les comptes d'avant n'ont pas de source, et l'inventer serait
 * écrire un fait qu'on ne connaît pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->string('source', 60)->nullable()->after('is_demo');
        });
    }

    public function down(): void
    {
        Schema::table('owners', fn (Blueprint $table) => $table->dropColumn('source'));
    }
};
