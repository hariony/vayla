<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le vocabulaire des équipements.
 *
 * Une table, et non un enum : la liste s'allongera au contact des logements
 * réels (« panneaux solaires » n'était pas prévu, « paillote » non plus).
 * Ajouter une ligne est une opération de donnée ; changer le sens d'une
 * rubrique n'en est pas une, et c'est pour ça que le groupe, lui, est un
 * enum (App\Enums\AmenityGroup).
 *
 * `filterable` marque le sous-ensemble qui a droit à une case dans la
 * recherche. Tout n'y a pas sa place : personne ne cherche un logement par
 * « grille-pain », mais « groupe électrogène » ou « piscine privée »
 * décident d'un séjour.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('group');
            $table->string('icon')->default('dot');
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('filterable')->default(false);
            $table->timestamps();

            $table->index(['group', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenities');
    }
};
