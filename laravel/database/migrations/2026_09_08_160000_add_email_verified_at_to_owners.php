<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L'adresse e-mail devient le canal d'inscription, et sa preuve se retient.
 *
 * L'inscription passe par un code envoyé à l'adresse : c'est le seul canal
 * qui part automatiquement sans entreprise enregistrée. `email_verified_at`
 * note que ce code a été saisi — la même chose que `phone_verified_at` fait
 * pour le numéro, sur l'autre canal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });
    }
};
