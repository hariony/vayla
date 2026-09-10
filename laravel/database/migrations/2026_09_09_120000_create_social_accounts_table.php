<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les identités sociales rattachées à un compte voyageur.
 *
 * **Une table dédiée, pas des colonnes sur `users`.** Un `google_id`, un
 * `facebook_id` et un `apple_id` côte à côte obligeraient à une migration par
 * fournisseur ajouté, laisseraient trois colonnes vides sur presque toutes les
 * lignes, et rendraient impossible de retenir ce que chaque fournisseur a
 * répondu — deux d'entre eux peuvent donner deux adresses différentes pour la
 * même personne.
 *
 * **`(provider, provider_user_id)` est unique, et c'est la seule identité en
 * laquelle on a confiance.** L'e-mail ne l'est pas : il change, il se relaie
 * chez Apple, et il n'est pas toujours vérifié. L'identifiant du fournisseur,
 * lui, est stable et lui appartient.
 *
 * **Aucun jeton n'est stocké.** Ni `access_token`, ni `refresh_token` : nous
 * n'appelons aucune API du fournisseur après la connexion — nous voulons
 * savoir qui entre, pas agir en son nom. Les garder serait une dette de
 * sécurité sans contrepartie, et un vol de base deviendrait un vol de comptes
 * Google.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Le fournisseur et son identifiant : la seule identité stable.
            $table->string('provider', 20);
            $table->string('provider_user_id');

            // Ce que le fournisseur a bien voulu donner, et qui peut manquer :
            // Apple ne transmet le nom qu'à la **toute première** connexion,
            // Facebook peut ne pas donner d'adresse, et l'avatar est absent
            // partout dès que le compte est neuf.
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('avatar_url')->nullable();

            // Ce que le fournisseur affirme de l'adresse. Sans cette garantie,
            // l'adresse ne rattache jamais à un compte existant.
            $table->boolean('email_verified')->default(false);

            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
