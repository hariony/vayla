<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un logement porte plusieurs catégories du rail.
 *
 * La catégorie « Séjour confirmé » n'est délibérément PAS stockée ici :
 * elle se déduit de `trust_level`. Deux écritures pour un même fait, et
 * l'échelle de confiance finit par mentir sur une des deux.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_listing', function (Blueprint $table) {
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();

            $table->primary(['listing_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_listing');
    }
};
