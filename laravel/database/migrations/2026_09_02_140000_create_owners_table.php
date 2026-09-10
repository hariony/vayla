<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les propriétaires.
 *
 * La table existe parce que le modèle économique l'exige : c'est à eux
 * qu'une facture mensuelle est adressée, et une facture a besoin d'un
 * destinataire. Jusqu'ici une annonce n'appartenait à personne.
 *
 * `mobile_money` est un **numéro de téléphone**, celui vers lequel le
 * propriétaire enverra son règlement — pas un moyen de paiement, pas un
 * jeton, rien qui permette de débiter quoi que ce soit. Vayla n'encaisse
 * pas et ne stocke aucun identifiant financier : le propriétaire pousse
 * l'argent, on ne le tire jamais.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('city')->nullable();
            $table->string('mobile_money')->nullable();
            $table->string('mobile_money_operator')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('destination_id')
                ->constrained('owners')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
        });

        Schema::dropIfExists('owners');
    }
};
