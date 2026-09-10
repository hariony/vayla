<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le propriétaire devient un compte.
 *
 * **L'identifiant est le téléphone, pas l'adresse e-mail.** C'est par WhatsApp
 * qu'on joint les propriétaires malgaches, beaucoup n'ont pas d'adresse
 * qu'ils relèvent, et exiger un e-mail à l'inscription écarterait précisément
 * le public qu'on veut servir. La colonne `email` existe quand même, nullable :
 * ceux qui en ont une pourront la donner, et elle servira aux notifications —
 * jamais à se connecter.
 *
 * `password` est **nullable** : un propriétaire créé par Vayla pendant l'appel
 * de vérification n'en a pas encore. Il en pose un à sa première connexion,
 * qui se fait par le lien d'accès envoyé sur son WhatsApp. Le lien reste donc
 * en service — il n'ouvre plus l'espace directement, il ouvre le compte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('phone');
            $table->string('password')->nullable()->after('email');
            $table->timestamp('password_set_at')->nullable()->after('password');
            $table->timestamp('last_login_at')->nullable()->after('password_set_at');
            // Les propriétaires travaillent au téléphone : leur faire retaper
            // un mot de passe à chaque ouverture est le meilleur moyen qu'ils
            // n'ouvrent plus.
            $table->rememberToken();
        });

        // Le numéro devient un identifiant de connexion : il doit être unique.
        Schema::table('owners', function (Blueprint $table) {
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn(['email', 'password', 'password_set_at', 'last_login_at', 'remember_token']);
        });
    }
};
