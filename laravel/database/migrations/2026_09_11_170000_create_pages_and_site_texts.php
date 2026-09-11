<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les textes du site, tenus par l'équipe depuis le back-office.
 *
 * **Deux natures, deux tables.**
 *
 * - `pages` — les pages éditoriales entières : « Comment ça marche », tarifs,
 *   guide du propriétaire, à propos, contact, mentions légales, conditions,
 *   confidentialité. Écrites en Markdown, publiées ou non, rangées dans une
 *   colonne du pied de page. Le pied de page ne montre que les pages
 *   publiées : il portait cinq liens `#` qui ne menaient nulle part.
 * - `site_texts` — les textes **dans** les pages construites : l'accroche de
 *   l'accueil, les titres de ses sections, le pied de page. Seules les
 *   valeurs **modifiées** y sont écrites ; le texte d'origine vit dans
 *   `SiteTextCatalog`, et « rétablir » supprime la ligne.
 *
 * `is_system` marque les pages que le pied de page et la loi attendent : on
 * les dépublie, on ne les supprime pas, et leur adresse ne bouge pas.
 * `internal_note` n'est lue que par l'équipe — « à faire relire par un
 * juriste » n'a rien à faire sur le site.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('title', 120);
            $table->string('lede', 300)->nullable();
            $table->text('body')->nullable();
            $table->string('seo_description', 170)->nullable();
            $table->string('footer_group', 20)->nullable();
            $table->unsignedSmallInteger('footer_position')->default(0);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_system')->default(false);
            $table->text('internal_note')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_published', 'footer_group', 'footer_position']);
        });

        Schema::create('site_texts', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->text('value');
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_texts');
        Schema::dropIfExists('pages');
    }
};
