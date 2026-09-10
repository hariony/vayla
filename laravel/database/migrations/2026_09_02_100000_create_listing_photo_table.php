<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La galerie d'une annonce.
 *
 * `position` porte l'ordre, et la **couverture est la position 0** — pas une
 * colonne à part. Un drapeau `cover` en plus de l'ordre aurait permis qu'une
 * couverture ne soit pas dans la galerie, ou que deux photos la revendiquent.
 *
 * Les photos restent partagées : une même photographie de Commons peut
 * illustrer une destination et une annonce, avec un seul crédit à honorer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_photo', function (Blueprint $table) {
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('photo_id')->constrained('photos')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);

            $table->primary(['listing_id', 'photo_id']);
            $table->index(['listing_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_photo');
    }
};
