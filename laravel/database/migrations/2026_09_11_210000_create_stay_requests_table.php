<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La demande de séjour « dans l'autre sens » : le voyageur décrit ce qu'il
 * cherche, l'équipe va le chercher chez les propriétaires.
 *
 * Tout est facultatif sauf ce qui permet de répondre : un nom, et un moyen de
 * joindre — WhatsApp ou e-mail. Ni compte, ni mot de passe : l'accueil le
 * promet (« sans créer de compte »).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stay_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->nullable()->constrained()->nullOnDelete();
            // Une région que l'atlas ne couvre pas encore, ou une précision.
            $table->string('place', 120)->nullable();
            $table->date('arrival')->nullable();
            $table->date('departure')->nullable();
            $table->unsignedTinyInteger('guests')->default(2);
            // En ariary, par nuit : le budget se dit dans la monnaie où il se paie.
            $table->unsignedInteger('budget')->nullable();
            $table->string('name', 120);
            $table->string('email', 190)->nullable();
            // E.164 : c'est par WhatsApp que l'équipe répond d'abord.
            $table->string('phone', 20)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('nouvelle')->index();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('taken_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('closing_note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stay_requests');
    }
};
