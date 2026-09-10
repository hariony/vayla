<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le voyageur entre par son adresse, et rien d'autre.
 *
 * **Un compte sans mot de passe, et sans nom au départ.** Le code reçu par
 * e-mail *est* la porte : le demander à chaque connexion prouve l'adresse à
 * chaque fois, ce qu'un mot de passe ne fait jamais. Il n'y a donc plus rien
 * à inventer, à retenir, ni à récupérer — et sur un premier compte en ligne,
 * c'est le mot de passe oublié qui fait perdre les gens, pas la connexion.
 *
 * **Le nom vient plus tard, quand il sert.** Il est demandé à la demande de
 * séjour, où il a une raison d'être — le propriétaire doit savoir qui arrive.
 * Le réclamer à l'inscription, c'était un champ de plus avant d'avoir rendu
 * le moindre service.
 *
 * Les deux colonnes deviennent donc nullables. On ne les supprime pas : un
 * voyageur qui pose un nom depuis son espace le garde, et
 * `bookings.traveller_email` continue de rattacher les séjours.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
