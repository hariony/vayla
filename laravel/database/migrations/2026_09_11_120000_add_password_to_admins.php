<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le back-office passe à l'adresse **et** au mot de passe.
 *
 * Le code par e-mail tenait pour les voyageurs et les propriétaires, qui
 * reviennent quelques fois par mois ; l'équipe, elle, ouvre le back-office
 * vingt fois par jour, et attendre un e-mail à chaque session n'est pas un
 * outil de travail.
 *
 * **`password_set_at` dit si le mot de passe est celui de la personne.** Un
 * mot de passe posé par quelqu'un d'autre — l'administrateur qui ajoute un
 * collègue, la commande qui crée le premier membre — est provisoire : il a
 * transité par un autre regard. Tant que la colonne est nulle, le back-office
 * n'ouvre qu'un seul écran, celui qui demande d'en choisir un.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
            $table->timestamp('password_set_at')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['password', 'password_set_at']);
        });
    }
};
