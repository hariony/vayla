<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La file des messages WhatsApp à envoyer.
 *
 * **Une table, et pas un envoi direct**, pour une raison qui n'est pas
 * technique : Vayla n'a pas encore d'entreprise enregistrée, et l'API WhatsApp
 * Business de Meta en exige une. Tant que ce n'est pas le cas, le message est
 * **écrit ici puis envoyé à la main** depuis le WhatsApp de Vayla, par un lien
 * `wa.me` prêt à cliquer. La file est donc la liste de travail de la personne
 * qui envoie.
 *
 * Ça vaudra aussi le jour où l'API arrive : un message qui part directement
 * dans un appel HTTP est un message perdu quand l'appel échoue. Écrit d'abord,
 * envoyé ensuite, et on sait toujours ce qui n'est pas parti.
 *
 * **Le corps est figé à l'écriture, jamais recomposé à l'envoi.** Un message
 * qui se régénère au moment de partir dirait « il vous reste 41 h » alors
 * qu'il en reste douze — et sur une demande qui expire, c'est le seul chiffre
 * qui compte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_messages', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 40);
            $table->string('to', 20);
            $table->text('body');

            // De qui et à propos de quoi : sert à ne pas envoyer deux fois le
            // même rappel, et à retrouver le contexte depuis la file.
            $table->foreignId('owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->cascadeOnDelete();

            $table->timestamp('sent_at')->nullable();
            $table->string('failure')->nullable();
            $table->timestamps();

            $table->index(['sent_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_messages');
    }
};
