<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De quoi laisser un propriétaire créer ses annonces et téléverser ses photos.
 *
 * Deux changements, et le premier est celui qui protège tout le produit.
 *
 * **`photos.folder`.** Jusqu'ici toutes les photos venaient de Wikimedia
 * Commons et vivaient dans `public/images/lieux/`, avec auteur, licence et
 * page source obligatoires — c'est ce qu'exigent CC BY et CC BY-SA. Les
 * photos qu'un propriétaire téléverse n'ont rien de tout ça : elles sont à
 * lui. Les mélanger dans le même dossier ferait deux choses également
 * fausses — elles apparaîtraient dans le bloc « Crédits photo » du pied de
 * page, et `PhotoFilesTest` les signalerait comme orphelines. Elles vont donc
 * dans `public/images/annonces/`, et la colonne dit laquelle est laquelle.
 *
 * **Auteur, licence et source deviennent nullables.** Une photo de
 * propriétaire n'a pas de licence à citer : exiger un texte reviendrait à en
 * inventer un, et un crédit inventé sur un site dont l'argument est la
 * vérification est exactement ce qu'on ne peut pas se permettre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->string('folder')->default('lieux')->after('key');
            $table->string('author')->nullable()->change();
            $table->string('licence')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('folder');
        });
    }
};
