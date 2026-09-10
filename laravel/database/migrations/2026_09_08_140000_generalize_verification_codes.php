<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le code à usage unique cesse d'être propre au téléphone.
 *
 * L'inscription passe désormais par l'**adresse e-mail**, pour le voyageur
 * comme pour le propriétaire — c'est le seul canal qui part automatiquement
 * sans entreprise enregistrée ni carte bancaire. Le téléphone reste, mais
 * plus tard.
 *
 * **Les cinq bornes ne changent pas d'un canal à l'autre** : hachage en base,
 * essais comptés par code, délai de renvoi, plafond horaire, invalidation du
 * précédent. Les réécrire pour l'e-mail aurait garanti qu'une des deux
 * versions finisse par mentir — d'où une seule table et un `kind` qui dit
 * seulement où le code est parti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('phone_verifications', 'verification_codes');

        Schema::table('verification_codes', function (Blueprint $table) {
            $table->renameColumn('phone', 'destination');
        });

        Schema::table('verification_codes', function (Blueprint $table) {
            // `phone` ou `email` : ce qu'on vérifie, pas par quel service.
            // Le service, c'est `channel` — un code de téléphone peut partir
            // par WhatsApp ou par SMS sans que sa nature change.
            $table->string('kind', 10)->default('phone')->after('destination');
            $table->string('destination', 190)->change();
        });
    }

    public function down(): void
    {
        Schema::table('verification_codes', function (Blueprint $table) {
            $table->dropColumn('kind');
            $table->renameColumn('destination', 'phone');
        });

        Schema::rename('verification_codes', 'phone_verifications');
    }
};
