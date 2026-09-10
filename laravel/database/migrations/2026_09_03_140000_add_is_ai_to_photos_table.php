<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une image générée n'est pas une photographie, et doit le dire.
 *
 * Les annonces de démonstration ont besoin d'intérieurs — chambre, séjour,
 * cuisine, piscine — qu'aucune banque d'images libre ne fournit pour
 * Madagascar. Faute de mieux elles seront générées. Mais Vayla ne vend qu'une
 * chose, la vérification : une image fabriquée qui passerait pour une photo
 * prise sur place détruirait l'argument entier, et plus sûrement qu'une page
 * laide. `is_ai` porte ce marquage jusque dans la visionneuse et le pied de
 * page — le bandeau « Aperçu » du haut de fiche parle de l'annonce, pas de
 * l'image.
 *
 * `source_url` devient nullable : une image générée n'a pas de page source.
 * `author` reste obligatoire et porte le nom du modèle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->boolean('is_ai')->default(false)->after('width');
            $table->string('source_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('is_ai');
        });
    }
};
