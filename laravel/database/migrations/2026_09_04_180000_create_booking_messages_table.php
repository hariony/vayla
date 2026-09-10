<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * L'échange entre un voyageur et un propriétaire, attaché à sa réservation.
 *
 * **Un fil par réservation, jamais une messagerie générale.** Vayla ne cache
 * pas les numéros de téléphone — tout le modèle est la mise en relation
 * directe — et une messagerie qui viendrait concurrencer WhatsApp perdrait :
 * les deux parties y sont déjà, et le va-et-vient rapide s'y fera de toute
 * façon. Ce que WhatsApp ne donne pas, c'est **une trace rattachée à un
 * séjour** : le jour où quelqu'un affirme qu'on lui avait promis la
 * climatisation, il faut pouvoir relire ce qui a été écrit et à quelle date.
 *
 * D'où trois choix :
 *
 * — **Pas de conversation sans réservation.** Un « contacter le propriétaire »
 *   ouvert à tous serait une surface de démarchage sans responsabilité.
 * — **`author` est un rôle, pas un identifiant.** Un voyageur n'a pas de
 *   compte sur Vayla — c'est délibéré — et le fil doit rester lisible quand
 *   la réservation change de main côté propriétaire.
 * — **Vayla peut lire.** C'est écrit à l'écran plutôt que caché : une trace
 *   dont personne ne sait qu'elle est lisible ne sert de médiation à personne.
 *
 * Les deux horodatages de lecture vivent sur `bookings` et non sur chaque
 * message : « depuis quand cette personne n'a pas ouvert le fil » suffit à
 * compter les non-lus, et évite une ligne d'état par message et par partie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('author', 20);
            $table->text('body');
            $table->timestamps();

            $table->index(['booking_id', 'created_at']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('owner_read_at')->nullable()->after('closed_reason');
            $table->timestamp('traveller_read_at')->nullable()->after('owner_read_at');
        });

        /*
         * Le message déposé avec la demande devient **le premier message du
         * fil**. Le laisser dans sa colonne aurait fait deux endroits où
         * vivent les mots d'un voyageur, et l'écran aurait fini par n'en
         * afficher qu'un des deux.
         */
        foreach (DB::table('bookings')->whereNotNull('message')->get() as $booking) {
            if (trim((string) $booking->message) === '') {
                continue;
            }

            DB::table('booking_messages')->insert([
                'booking_id' => $booking->id,
                'author' => 'traveller',
                'body' => $booking->message,
                'created_at' => $booking->created_at,
                'updated_at' => $booking->created_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['owner_read_at', 'traveller_read_at']);
        });

        Schema::dropIfExists('booking_messages');
    }
};
