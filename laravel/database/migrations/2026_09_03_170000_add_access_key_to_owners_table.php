<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La clé qui ouvre l'espace propriétaire.
 *
 * **Pas de mot de passe, et c'est un choix, pas un raccourci.** Les
 * propriétaires sont joints par WhatsApp, souvent sur un téléphone modeste, et
 * beaucoup n'ont jamais créé de compte en ligne. Leur demander d'en inventer
 * un — puis de le retrouver deux mois plus tard, au moment précis où une
 * demande de réservation expire dans quarante-huit heures — est le meilleur
 * moyen de perdre la demande. Le lien envoyé au message est l'entrée.
 *
 * C'est le même modèle que `/reservations/{reference}`, déjà en place côté
 * voyageur : la clé **est** le porteur du droit d'accès. Deux garde-fous en
 * conséquence : elle est longue et non devinable, et elle n'ouvre **aucune
 * écriture d'argent** — l'espace accepte ou refuse des demandes, il ne débite
 * rien et ne modifie aucun tarif. Le jour où l'écriture s'étend, c'est
 * `auth:sanctum` qui prend le relais, pas une clé plus longue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->string('access_key', 40)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn('access_key');
        });
    }
};
