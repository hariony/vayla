<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photographies de lieux. Chaque ligne porte son crédit : les licences
 * CC BY et CC BY-SA l'exigent, et le pied de page les affiche toutes.
 * Le fichier vit dans public/images/lieux/<key>.webp — `key` est donc
 * l'identifiant naturel, l'id auto-incrémenté ne sert qu'aux clés étrangères.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('caption');
            $table->string('author');
            $table->string('licence');
            $table->string('licence_url')->nullable();
            $table->string('source_url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
