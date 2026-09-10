<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce qu'un logement possède.
 *
 * Deux colonnes de pivot, et chacune répond à un besoin qu'une simple
 * association ne couvrait pas :
 *
 * - `highlight` : les trois équipements que l'annonce met en avant sur sa
 *   carte. C'est un choix éditorial propre à CE logement — une piscine est
 *   l'argument d'une villa balnéaire et un détail dans un lodge de forêt —
 *   donc il vit sur le lien, pas sur l'équipement.
 * - `note` : la précision du propriétaire (« démarrage automatique »,
 *   « 8 × 4 m, non chauffée »). Elle évite de multiplier les entrées du
 *   vocabulaire pour chaque nuance.
 *
 * Clé primaire composite : un logement ne peut pas posséder deux fois le
 * même équipement, et la base le garantit plutôt que le code applicatif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amenity_listing', function (Blueprint $table) {
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained('amenities')->cascadeOnDelete();
            $table->boolean('highlight')->default(false);
            $table->string('note')->nullable();

            $table->primary(['listing_id', 'amenity_id']);
            $table->index('amenity_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenity_listing');
    }
};
