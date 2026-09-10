<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les codes à usage unique envoyés pour prouver un numéro.
 *
 * **Le code est stocké haché, jamais en clair.** Une base copiée ne doit pas
 * donner des codes vivants : six chiffres se rejouent en une seconde. Le
 * hachage bcrypt rend l'attaque hors ligne inutile dans la fenêtre de dix
 * minutes où le code vaut quelque chose.
 *
 * **`attempts` est sur le code, pas sur le numéro.** Un compteur par numéro
 * permettrait de bloquer quelqu'un en épuisant ses essais depuis l'extérieur ;
 * un compteur par code limite le tirage au sort sans jamais fermer la porte à
 * son propriétaire, qui peut en redemander un.
 *
 * **On garde les lignes après usage.** Elles servent à deux limites qui
 * comptent : le délai avant renvoi, et le nombre de codes par heure — sans
 * quoi un inconnu peut faire payer à Vayla l'envoi de vingt messages sur le
 * téléphone de quelqu'un d'autre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('channel', 20)->default('log');
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['phone', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_verifications');
    }
};
