<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Une destination porte une **galerie**, pas une image — comme une annonce.
 *
 * La position 0 est la couverture : la photo de l'atlas et de l'en-tête de la
 * page. `destinations.photo_id` reste, parce que l'atlas, l'accueil et l'API
 * la lisent partout ; mais **elle n'a plus qu'un seul écrivain**,
 * `OfficeContentService::synchroniserCouverture()`, qui la recopie depuis la
 * position 0 à chaque geste sur la galerie. Un test vérifie qu'elles ne
 * divergent jamais.
 *
 * La photo déjà posée sur chaque destination devient la première de sa
 * galerie : rien ne change à l'écran le jour de la migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destination_photo', function (Blueprint $table) {
            $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('photo_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);

            $table->primary(['destination_id', 'photo_id']);
            $table->index(['destination_id', 'position']);
        });

        DB::table('destinations')->whereNotNull('photo_id')->orderBy('id')->get(['id', 'photo_id'])
            ->each(fn ($d) => DB::table('destination_photo')->insert([
                'destination_id' => $d->id, 'photo_id' => $d->photo_id, 'position' => 0,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('destination_photo');
    }
};
