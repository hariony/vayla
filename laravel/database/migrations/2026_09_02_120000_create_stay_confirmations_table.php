<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les séjours confirmés par les voyageurs.
 *
 * Ce n'est pas une table d'avis : **il n'y a pas de note**. Un voyageur
 * coche des faits (`points`) et signale ce qui n'allait pas (`flagged`).
 * Ajouter une colonne `rating` reviendrait à réintroduire la moyenne
 * étoilée que tout le reste du produit refuse — et une moyenne finit
 * toujours par remplacer les faits qu'elle résume.
 *
 * `flagged` est aussi important que `points` : c'est ce qui distingue une
 * confirmation d'un témoignage complaisant. Une plateforme qui ne stockerait
 * que les « oui » ne pourrait rien afficher d'autre que des « oui ».
 *
 * C'est cette table qui fait passer une annonce au niveau 4 de l'échelle de
 * confiance, et c'est l'événement facturable au propriétaire. Elle est donc
 * la pièce la plus sensible du domaine : `confirmed_at` fait foi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stay_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();

            // Prénom seul : le voyageur confirme un fait, il ne signe pas une
            // tribune. Aucune donnée qui permette de le retrouver.
            $table->string('traveller');
            $table->string('traveller_from')->nullable();

            $table->unsignedSmallInteger('nights');
            $table->date('stayed_on');

            $table->json('points');           // ConfirmationPoint[] confirmés
            $table->json('flagged')->nullable(); // ConfirmationPoint[] signalés
            $table->text('comment')->nullable();
            $table->text('mismatch')->nullable(); // ce qui n'allait pas, en clair

            $table->boolean('is_demo')->default(false);
            $table->timestamp('confirmed_at');
            $table->timestamps();

            $table->index(['listing_id', 'confirmed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stay_confirmations');
    }
};
