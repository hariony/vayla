<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rail de catégories de l'accueil. C'est un filtre, pas une galerie : d'où
 * `icon`, un pictogramme au trait, et jamais une photo.
 *
 * `position` porte l'ordre d'affichage. Cette position a vocation à être
 * vendue : le titre du rail reste donc éditorial, et une place achetée
 * devra porter `sponsored` — la colonne existe pour que le jour venu la
 * mention soit une donnée, pas une décision d'affichage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('icon');
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('sponsored')->default(false);
            $table->timestamps();

            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
