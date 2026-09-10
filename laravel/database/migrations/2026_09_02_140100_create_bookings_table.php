<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les réservations.
 *
 * Vayla n'encaisse rien : une réservation ne transporte pas d'argent, elle
 * **met en relation et bloque des dates**. L'acompte, s'il y en a un, se
 * convient entre le voyageur et le propriétaire, hors plateforme.
 *
 * Trois colonnes figées à la réservation, et c'est le cœur de l'affaire :
 *
 * - `price_per_night` et `nights` : le total ne doit pas changer si le
 *   propriétaire réévalue son tarif trois semaines plus tard. Une facture
 *   qui bouge après coup est une facture qu'on ne paie pas.
 * - `commission_rate` : le taux applicable est celui du jour de la
 *   réservation. Augmenter la commission ne doit jamais s'appliquer
 *   rétroactivement aux séjours déjà engagés.
 *
 * `hold_expires_at` protège le propriétaire : une demande sans réponse rend
 * ses nuits au calendrier au lieu de le fermer indéfiniment.
 *
 * `reference` est un code court, lisible au téléphone : c'est ce que le
 * voyageur et le propriétaire vont s'échanger sur WhatsApp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();

            $table->string('traveller');
            $table->string('traveller_phone');
            $table->string('traveller_email')->nullable();
            $table->unsignedSmallInteger('guests')->default(1);
            $table->text('message')->nullable();

            $table->date('arrival');
            $table->date('departure');
            $table->unsignedSmallInteger('nights');

            // Figés à la réservation : voir le bloc ci-dessus.
            $table->unsignedInteger('price_per_night');
            $table->unsignedInteger('total');
            $table->decimal('commission_rate', 5, 4);

            $table->string('status')->default('pending');
            $table->timestamp('hold_expires_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('closed_reason')->nullable();

            $table->boolean('is_demo')->default(false);
            $table->timestamps();

            $table->index(['listing_id', 'status', 'arrival']);
            $table->index(['status', 'hold_expires_at']);
        });

        Schema::table('stay_confirmations', function (Blueprint $table) {
            // La confirmation rattachée à sa réservation : c'est ce lien qui
            // rend une nuit facturable. Une confirmation orpheline reste un
            // témoignage, elle ne produit pas de ligne de facture.
            $table->foreignId('booking_id')->nullable()->after('listing_id')
                ->constrained('bookings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stay_confirmations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_id');
        });

        Schema::dropIfExists('bookings');
    }
};
