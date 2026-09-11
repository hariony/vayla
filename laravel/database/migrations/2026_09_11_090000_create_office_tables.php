<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le back-office : ceux qui y entrent, ce qu'ils y font, et ce qui a été payé.
 *
 * **Les administrateurs ont leur table, jamais un drapeau sur `users`.** Un
 * booléen `is_admin` sur le compte voyageur ferait de chaque faille de
 * l'espace client une faille du back-office, et d'une inscription réussie un
 * pas vers l'administration. Une table à part, une garde à part, une session
 * à part — et aucune route d'inscription : un administrateur se crée en ligne
 * de commande ou par un autre administrateur.
 *
 * **Le journal est la contrepartie du pouvoir de publier.** Mettre en ligne,
 * attribuer un niveau de confiance, annuler une réservation : ce sont les
 * gestes qui engagent la promesse de Vayla. Chacun laisse une ligne — qui,
 * quoi, quand. Le nom de l'administrateur y est **recopié** : retirer quelqu'un
 * de l'équipe ne doit pas rendre ses décisions anonymes.
 *
 * **Le règlement d'une facture se consigne, il ne se calcule pas.** Vayla
 * n'encaisse rien : le propriétaire pousse son règlement par mobile money, et
 * c'est la seule trace que l'argent est arrivé. Un par propriétaire et par
 * mois — la facture est mensuelle.
 *
 * **La note de vérification** est ce que Vayla demande au propriétaire quand
 * elle lui renvoie sa fiche : un renvoi sans motif le laisserait deviner ce qui
 * manque, et il ne devinerait pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('email')->unique();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('admin_name', 80);
            $table->string('kind', 40)->index();
            // Une réservation, une annonce, un propriétaire… Pas de morphTo :
            // le journal se lit, il ne se navigue pas par Eloquent.
            $table->string('subject_type', 20)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('summary', 300);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('invoice_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            // Le premier jour du mois facturé.
            $table->date('month');
            $table->unsignedInteger('amount');
            $table->string('reference', 60)->nullable();
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('settled_at');
            $table->timestamps();

            $table->unique(['owner_id', 'month']);
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->text('review_note')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('review_note');
        });

        Schema::dropIfExists('invoice_settlements');
        Schema::dropIfExists('admin_actions');
        Schema::dropIfExists('admins');
    }
};
