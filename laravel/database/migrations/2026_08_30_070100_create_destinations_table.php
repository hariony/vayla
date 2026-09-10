<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Destinations ouvertes. On n'ouvre une région qu'avec un correspondant sur
 * place : `opened_at` date cette ouverture, elle n'est pas décorative.
 *
 * `scene` désigne la variante d'illustration SVG servant de repli quand une
 * photo manque (voir Components/SceneArt.vue).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('region');

            // La façade climatique : ce qui permet au calendrier de dire
            // « bonne idée ou pas », et pas seulement « libre ».
            $table->string('climate_zone')->default('hautes-terres');
            $table->string('tagline');
            $table->string('scene')->default('lagoon');
            $table->foreignId('photo_id')->nullable()->constrained('photos')->nullOnDelete();
            $table->boolean('featured')->default(false);
            $table->date('opened_at')->nullable();
            $table->timestamps();

            $table->index('featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destinations');
    }
};
