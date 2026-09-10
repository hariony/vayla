<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les logements.
 *
 * `trust_level` est la colonne qui distingue Vayla d'un mur de photos :
 * elle porte l'échelle à quatre niveaux (App\Enums\TrustLevel).
 *
 * `is_demo` marque les annonces fictives qui n'existent que pour dessiner
 * et éprouver la grille. Elles sont exclues dès que `vayla.demo` passe à
 * false — voir config/vayla.php.
 *
 * Ni `place` ni `region` ici : ils se lisent sur la destination. Une seule
 * source de vérité, sinon les deux divergent au premier renommage.
 *
 * Pas de `photo_id` non plus : une annonce porte une galerie, pas une image.
 * Elle vit dans le pivot `listing_photo`, et la couverture est simplement la
 * photo de position 0. Garder en plus une colonne `photo_id` aurait créé deux
 * écritures pour un même fait — la couverture aurait fini par ne plus être
 * dans la galerie.
 *
 * Aucune colonne d'équipement non plus : ils vivent dans `amenities`, via le
 * pivot `amenity_listing`. Une colonne `piscine` booléenne aurait obligé à
 * une migration par équipement ajouté, et le texte libre de l'ancien `perks`
 * ne se filtrait pas — « Wifi fibre », « wifi », « WIFI » étaient trois
 * choses différentes pour la base.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->foreignId('destination_id')->constrained('destinations')->cascadeOnDelete();
            $table->string('scene')->default('lagoon');
            $table->string('kind')->default('villa');
            $table->text('summary')->nullable();
            $table->text('description')->nullable();

            // Capacité. `beds` n'est pas `bedrooms` : trois chambres peuvent
            // compter cinq couchages, et c'est le chiffre qui décide pour un
            // groupe.
            $table->unsignedSmallInteger('guests')->default(2);
            $table->unsignedSmallInteger('bedrooms')->default(1);
            $table->unsignedSmallInteger('beds')->default(1);
            $table->unsignedSmallInteger('bathrooms')->default(1);
            $table->unsignedSmallInteger('surface')->nullable();

            // Prix à la nuit, en ariary, entier : pas de flottant sur de
            // l'argent. `min_nights` borne les séjours d'une nuit là où le
            // propriétaire n'en veut pas.
            $table->unsignedInteger('price');
            $table->unsignedSmallInteger('min_nights')->default(1);
            $table->unsignedSmallInteger('max_nights')->nullable();

            // Le règlement, en colonnes et non en texte libre : « pas
            // d'animaux » doit pouvoir devenir un filtre, et un paragraphe
            // ne se filtre pas.
            $table->time('check_in_from')->default('14:00');
            $table->time('check_out_before')->default('11:00');
            $table->boolean('pets_allowed')->default(false);
            $table->boolean('smoking_allowed')->default(false);
            $table->boolean('events_allowed')->default(false);

            $table->unsignedTinyInteger('trust_level')->default(1);
            $table->boolean('featured')->default(false);
            $table->boolean('is_demo')->default(false);
            $table->string('status')->default('published');
            $table->timestamps();

            $table->index(['status', 'trust_level']);
            $table->index('destination_id');
            $table->index(['status', 'guests']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
