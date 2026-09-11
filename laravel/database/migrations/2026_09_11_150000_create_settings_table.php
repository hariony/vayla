<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les réglages que l'équipe tient depuis le back-office : le taux de change
 * affiché à côté de l'ariary, et le taux de commission des nouvelles demandes.
 *
 * **Une table clé-valeur, pas des colonnes** : deux réglages aujourd'hui, et
 * chacun garde qui l'a changé et quand. Le `.env` reste le **repli** — une base
 * neuve, ou une ligne absente, retombe sur `config('vayla.*')` : un réglage
 * manquant ne doit jamais faire tomber une page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->string('value', 200);
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
