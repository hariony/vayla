<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un compte peut exister sans adresse e-mail.
 *
 * **Tous les fournisseurs n'en donnent pas.** Facebook n'en transmet pas quand
 * le compte a été ouvert avec un numéro de téléphone, et l'utilisateur peut
 * refuser la permission. Rendre l'inscription impossible dans ce cas
 * reviendrait à afficher un bouton qui échoue une fois sur dix, sans que
 * personne ne comprenne pourquoi.
 *
 * **La conséquence est réelle et assumée** : un compte sans adresse ne peut
 * pas rattacher de réservations — `bookings.traveller_email` est la clé de
 * `/mes-reservations` — et ne peut se reconnecter que par le même fournisseur,
 * puisque notre autre porte est un code envoyé par e-mail. Le compte reste
 * utilisable, il est seulement diminué ; c'est à l'utilisateur d'ajouter une
 * adresse, pas à nous d'en inventer une.
 *
 * `unique` tient toujours : PostgreSQL comme SQLite acceptent plusieurs NULL
 * dans un index unique — deux comptes sans adresse ne se marchent pas dessus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
