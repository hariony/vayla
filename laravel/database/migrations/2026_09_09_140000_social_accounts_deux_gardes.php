<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une identité sociale peut désigner un voyageur **ou** un propriétaire.
 *
 * **Deux gardes, deux entités authentifiables** — c'est l'architecture du
 * projet depuis le début : un propriétaire n'est pas une ligne de `users`, il
 * porte des logements, des réservations, une facture. Un `user_id` en dur dans
 * `social_accounts` interdisait donc « Continuer avec Google » sur l'écran du
 * propriétaire, ou pire, l'y connectait comme voyageur.
 *
 * **La relation devient polymorphe**, et l'unicité avec elle : la clé est
 * `(compte_type, provider, provider_user_id)` et non plus
 * `(provider, provider_user_id)`. La nuance compte — **une même personne peut
 * être voyageuse et propriétaire**, avec le même compte Google. Deux sessions
 * distinctes, c'est le principe des deux gardes ; une contrainte trop large
 * aurait interdit le second rôle sans que personne ne comprenne pourquoi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropUnique(['provider', 'provider_user_id']);
            // L'index d'abord : SQLite refuse de supprimer une colonne encore
            // référencée par un index, là où PostgreSQL l'emporte avec elle.
            // Les tests tournent sur SQLite — sans ça, la suite entière tombe.
            $table->dropIndex(['user_id']);
            $table->dropConstrainedForeignId('user_id');

            $table->morphs('compte');
            $table->unique(['compte_type', 'provider', 'provider_user_id'], 'social_identite_unique');
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropUnique('social_identite_unique');
            $table->dropMorphs('compte');

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['provider', 'provider_user_id']);
        });
    }
};
